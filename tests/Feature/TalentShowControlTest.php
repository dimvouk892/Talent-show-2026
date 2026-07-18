<?php

namespace Tests\Feature;

use App\Enums\TalentShowStatus;
use App\Enums\TeamStatus;
use App\Models\Team;
use App\Models\Vote;
use App\Models\VoteRevision;
use App\Services\ScoreCalculationService;
use App\Services\TalentShowControlService;
use App\Services\VoteService;
use App\Livewire\Admin\LiveControl;
use App\Livewire\Admin\TalentShows\Edit;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TalentShowTestCase;

class TalentShowControlTest extends TalentShowTestCase
{
    protected function voteForCurrentTeam(): void
    {
        $team = $this->show->currentTeam;
        foreach ($this->show->judges as $judge) {
            app(VoteService::class)->submit($judge, $team, 10);
        }
    }

    public function test_reveal_scores_works_with_partial_votes(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        app(VoteService::class)->submit($judge, $this->show->currentTeam, 9);

        $this->show->update(['show_live_scores' => false]);

        app(TalentShowControlService::class)->revealScores($this->show->fresh());

        $this->assertTrue($this->show->fresh()->show_live_scores);
    }

    public function test_votes_do_not_auto_reveal_live_scores_on_presentation(): void
    {
        $this->openScoring();
        $this->assertFalse($this->show->show_live_scores);

        app(VoteService::class)->submit($this->show->judges()->first(), $this->show->currentTeam, 10);

        $this->assertFalse($this->show->fresh()->show_live_scores);

        app(TalentShowControlService::class)->revealScores($this->show->fresh());

        $this->assertTrue($this->show->fresh()->show_live_scores);
    }

    public function test_start_voting_button_hidden_after_scoring_starts(): void
    {
        $control = app(TalentShowControlService::class);

        $this->assertTrue($control->canOpenScoring($this->show));

        $this->openScoring();
        $this->assertFalse($control->canOpenScoring($this->show->fresh()));

        $this->show->update(['status' => \App\Enums\TalentShowStatus::ScoringClosed]);
        $this->assertFalse($control->canOpenScoring($this->show->fresh()));
    }

    public function test_partial_scores_visible_before_all_judges_vote(): void
    {
        $this->openScoring();
        app(VoteService::class)->submit($this->show->judges()->first(), $this->show->currentTeam, 9);

        $scores = app(ScoreCalculationService::class)->forTeam($this->show->currentTeam->fresh(), $this->show);

        $this->assertFalse($scores['is_complete']);
        $this->assertEquals(9, $scores['total_score']);
        $this->assertEquals(1, $scores['votes_count']);
    }

    public function test_next_team_rejected_before_all_vote(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        app(VoteService::class)->submit($judge, $this->show->currentTeam, 9);

        $this->expectException(InvalidArgumentException::class);
        app(TalentShowControlService::class)->nextTeam($this->show);
    }

    public function test_only_one_team_active(): void
    {
        $this->openScoring();

        $activeCount = Team::where('talent_show_id', $this->show->id)
            ->where('status', TeamStatus::Active)
            ->count();

        $this->assertEquals(1, $activeCount);
    }

    public function test_next_team_activates_correctly(): void
    {
        $this->openScoring();
        $firstTeam = $this->show->currentTeam;
        $this->voteForCurrentTeam();

        app(TalentShowControlService::class)->nextTeam($this->show->fresh());
        $this->show->refresh();

        $this->assertEquals(TeamStatus::Presented, $firstTeam->fresh()->status);
        $this->assertNotEquals($firstTeam->id, $this->show->current_team_id);
    }

    public function test_last_team_completes_show(): void
    {
        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show);
        $teams = $this->show->activeTeams()->ordered()->get();
        $roundScores = [9, 10, 12];

        foreach ($teams as $index => $team) {
            $this->show->refresh();
            foreach ($this->show->judges as $judge) {
                app(VoteService::class)->submit($judge, $this->show->currentTeam, $roundScores[$index % 3]);
            }
            $control->nextTeam($this->show->fresh());
        }

        $this->show->refresh();
        $this->assertEquals(TalentShowStatus::ScoringClosed, $this->show->status);
        $this->assertNull($this->show->current_team_id);
    }

    public function test_total_score_calculated_correctly(): void
    {
        $this->openScoring();
        $team = $this->show->currentTeam;
        $scores = [10, 12, 9, 10, 12];

        foreach ($this->show->judges as $i => $judge) {
            app(VoteService::class)->submit($judge, $team, $scores[$i]);
        }

        $result = app(ScoreCalculationService::class)->forTeam($team->fresh());
        $this->assertEquals(53, $result['total_score']);
    }

    public function test_total_score_is_sum_of_votes(): void
    {
        $this->openScoring();
        $team = $this->show->currentTeam;

        foreach ($this->show->judges as $judge) {
            app(VoteService::class)->submit($judge, $team, 10);
        }

        $result = app(ScoreCalculationService::class)->forTeam($team->fresh());
        $this->assertEquals(50, $result['total_score']);
    }

    public function test_maximum_score_is_correct(): void
    {
        $this->openScoring();
        $result = app(ScoreCalculationService::class)->forTeam($this->show->currentTeam);
        $this->assertEquals(60, $result['maximum_score']);
    }

    public function test_cannot_start_without_active_judges(): void
    {
        $this->show->judges()->update(['is_active' => false]);

        $this->expectException(InvalidArgumentException::class);
        app(TalentShowControlService::class)->startShow($this->show);
    }

    public function test_cannot_start_without_teams(): void
    {
        Team::where('talent_show_id', $this->show->id)->delete();

        $this->expectException(InvalidArgumentException::class);
        app(TalentShowControlService::class)->startShow($this->show);
    }

    public function test_cannot_delete_team_with_votes(): void
    {
        $this->openScoring();
        $team = $this->show->currentTeam;
        app(VoteService::class)->submit($this->show->judges()->first(), $team, 9);

        $this->assertTrue($team->hasVotes());
    }

    public function test_cannot_delete_judge_with_votes(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        app(VoteService::class)->submit($judge, $this->show->currentTeam, 9);

        $this->assertTrue($judge->hasVotes());
    }

    public function test_restart_show_clears_votes_and_resets_state(): void
    {
        $this->openScoring();
        $this->voteForCurrentTeam();

        $control = app(TalentShowControlService::class);
        $control->nextTeam($this->show->fresh());

        $this->assertGreaterThan(0, Vote::where('talent_show_id', $this->show->id)->count());

        $control->restartShow($this->show->fresh());

        $this->show->refresh();

        $this->assertEquals(0, Vote::where('talent_show_id', $this->show->id)->count());
        $this->assertEquals(0, VoteRevision::count());
        $this->assertEquals(TalentShowStatus::ScoringOpen, $this->show->status);
        $this->assertNotNull($this->show->current_team_id);
        $this->assertFalse($this->show->show_live_scores);
        $this->assertFalse($this->show->show_ranking);
        $this->assertFalse($this->show->winner_revealed);
        $this->assertEquals(0, $this->show->podium_reveal_step);

        $this->assertEquals(1, Team::where('talent_show_id', $this->show->id)
            ->where('status', TeamStatus::Active)
            ->count());

        $this->assertEquals(
            $this->show->teams()->count() - 1,
            Team::where('talent_show_id', $this->show->id)->where('status', TeamStatus::Pending)->count(),
        );
    }

    public function test_clear_scores_deletes_votes_without_restarting_scoring(): void
    {
        $this->openScoring();
        $this->voteForCurrentTeam();

        app(TalentShowControlService::class)->clearScores($this->show->fresh());
        $this->show->refresh();

        $this->assertEquals(0, Vote::where('talent_show_id', $this->show->id)->count());
        $this->assertEquals(0, VoteRevision::count());
        $this->assertEquals(TalentShowStatus::Ready, $this->show->status);
        $this->assertNull($this->show->current_team_id);
        $this->assertEquals(
            $this->show->teams()->count(),
            Team::where('talent_show_id', $this->show->id)->where('status', TeamStatus::Pending)->count(),
        );
    }

    public function test_clear_scores_via_settings_leaves_show_waiting(): void
    {
        $this->openScoring();
        app(VoteService::class)->submit($this->show->judges()->first(), $this->show->currentTeam, 10);

        Livewire::actingAs($this->admin)
            ->test(Edit::class, ['talentShow' => $this->show])
            ->call('askClearScores')
            ->assertSet('showClearScoresConfirm', true)
            ->call('confirmClearScores')
            ->assertSet('showClearScoresConfirm', false)
            ->assertSet(
                'flashSuccess',
                'Οι βαθμολογίες διαγράφηκαν. Η εκδήλωση είναι σε αναμονή — πατήστε «Έναρξη» στον Ζωντανό Έλεγχο.'
            );

        $this->assertEquals(0, Vote::where('talent_show_id', $this->show->id)->count());
        $this->assertEquals(TalentShowStatus::Ready, $this->show->fresh()->status);

        Livewire::actingAs($this->admin)
            ->test(LiveControl::class, ['talentShow' => $this->show->fresh()])
            ->call('openScoring')
            ->assertSet('flashSuccess', 'Η ψηφοφορία ξεκίνησε.');

        $this->assertEquals(TalentShowStatus::ScoringOpen, $this->show->fresh()->status);
    }

    public function test_restart_show_allows_judges_to_vote_again(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        app(VoteService::class)->submit($judge, $this->show->currentTeam, 10);

        app(TalentShowControlService::class)->restartShow($this->show->fresh());

        $this->show->refresh();
        $team = $this->show->currentTeam;

        $vote = app(VoteService::class)->submit($judge, $team, 9);

        $this->assertEquals(9, $vote->score);
        $this->assertEquals(1, Vote::where('talent_show_id', $this->show->id)->count());
    }

    public function test_live_control_buttons_show_feedback_in_component(): void
    {
        Livewire::actingAs($this->admin)
            ->test(LiveControl::class, ['talentShow' => $this->show])
            ->call('openScoring')
            ->assertSet('flashSuccess', 'Η ψηφοφορία ξεκίνησε.')
            ->assertSee('Η ψηφοφορία ξεκίνησε.');
    }

    public function test_can_start_with_one_active_judge(): void
    {
        $this->show->judges()->update(['is_active' => false]);
        $this->show->judges()->first()->update(['is_active' => true]);

        app(TalentShowControlService::class)->startShow($this->show->fresh());

        $this->assertEquals(TalentShowStatus::Ready, $this->show->fresh()->status);
    }

    public function test_live_control_shows_error_when_no_active_judges(): void
    {
        $this->show->judges()->update(['is_active' => false]);

        Livewire::actingAs($this->admin)
            ->test(LiveControl::class, ['talentShow' => $this->show->fresh()])
            ->call('openScoring')
            ->assertSet('flashError', 'Απαιτείται τουλάχιστον 1 ενεργός κριτής.')
            ->assertSee('Απαιτείται τουλάχιστον 1 ενεργός κριτής.');
    }

    public function test_open_scoring_requires_waiting_state(): void
    {
        $this->openScoring();
        $this->voteForCurrentTeam();

        Livewire::actingAs($this->admin)
            ->test(LiveControl::class, ['talentShow' => $this->show->fresh()])
            ->call('openScoring')
            ->assertSet(
                'flashError',
                'Η εκδήλωση πρέπει να είναι σε αναμονή. Διαγράψτε τα σκορ από τις Ρυθμίσεις και μετά πατήστε Έναρξη.'
            );

        $this->assertGreaterThan(0, Vote::where('talent_show_id', $this->show->id)->count());
    }

    public function test_restart_works_with_any_number_of_active_judges(): void
    {
        $this->openScoring();
        app(VoteService::class)->submit($this->show->judges()->first(), $this->show->currentTeam, 10);

        $this->show->judges()->update(['is_active' => false]);
        $this->show->judges()->limit(2)->update(['is_active' => true]);

        app(TalentShowControlService::class)->restartShow($this->show->fresh());

        $this->assertEquals(0, Vote::where('talent_show_id', $this->show->id)->count());
        $this->assertEquals(TalentShowStatus::ScoringOpen, $this->show->fresh()->status);
    }

    protected function completeAllVotingWithDistinctScores(): void
    {
        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show);
        $scores = [12, 10, 9];

        foreach ($this->show->activeTeams()->ordered()->get() as $index => $team) {
            $this->show->refresh();
            $score = $scores[$index % count($scores)];
            foreach ($this->show->judges as $judge) {
                if ($judge->is_final_voter) {
                    continue;
                }
                app(VoteService::class)->submit($judge, $this->show->currentTeam, $score);
            }
            $control->nextTeam($this->show->fresh());
        }
    }

    public function test_podium_reveal_advances_from_last_to_first(): void
    {
        $this->completeAllVotingWithDistinctScores();
        $control = app(TalentShowControlService::class);
        $control->showRanking($this->show->fresh());

        $this->assertTrue($control->canStartPodiumReveal($this->show->fresh()));
        $this->assertFalse($control->canAdvancePodium($this->show->fresh()));

        $control->startPodiumReveal($this->show->fresh());
        $this->show->refresh();

        $this->assertEquals(1, $this->show->podium_reveal_step);
        $this->assertFalse($this->show->winner_revealed);

        $state = app(\App\Services\ResultsService::class)->getPodiumRevealState($this->show);
        $this->assertEquals(3, $state['total_steps']);
        $this->assertEquals(3, $state['current']['ranking_position']);

        $control->nextPodiumReveal($this->show->fresh());
        $this->show->refresh();
        $this->assertEquals(2, $this->show->podium_reveal_step);
        $this->assertEquals(2, app(\App\Services\ResultsService::class)->getPodiumRevealState($this->show)['current']['ranking_position']);

        $control->nextPodiumReveal($this->show->fresh());
        $this->show->refresh();

        $this->assertEquals(3, $this->show->podium_reveal_step);
        $this->assertTrue($this->show->winner_revealed);
        $this->assertEquals(TalentShowStatus::WinnerRevealed, $this->show->status);
        $this->assertEquals(1, app(\App\Services\ResultsService::class)->getPodiumRevealState($this->show)['current']['ranking_position']);
        $this->assertNotNull($this->show->winner_team_id);
    }

    public function test_can_start_podium_after_scoring_without_manual_ranking_step(): void
    {
        $this->completeAllVotingWithDistinctScores();
        $control = app(TalentShowControlService::class);

        $this->assertTrue($control->canStartPodiumReveal($this->show->fresh()));
        $this->assertFalse($this->show->fresh()->show_ranking);

        $control->startPodiumReveal($this->show->fresh());
        $this->show->refresh();

        $this->assertTrue($this->show->show_ranking);
        $this->assertEquals(1, $this->show->podium_reveal_step);
    }

    public function test_cannot_start_podium_with_pending_final_vote(): void
    {
        $this->show->judges()->skip(4)->first()->update(['is_final_voter' => true]);
        $this->completeAllVotingWithDistinctScores();

        $control = app(TalentShowControlService::class);
        $this->assertTrue($this->show->fresh()->hasPendingFinalVote());
        $this->assertFalse($control->canStartPodiumReveal($this->show->fresh()));
        $this->assertFalse($control->canShowRanking($this->show->fresh()));
    }

    public function test_podium_rewind_clears_winner_revealed(): void
    {
        $this->completeAllVotingWithDistinctScores();
        $control = app(TalentShowControlService::class);
        $control->showRanking($this->show->fresh());
        $control->startPodiumReveal($this->show->fresh());
        $control->nextPodiumReveal($this->show->fresh());
        $control->nextPodiumReveal($this->show->fresh());

        $this->show->refresh();
        $this->assertTrue($this->show->winner_revealed);

        $control->previousPodiumReveal($this->show->fresh());
        $this->show->refresh();

        $this->assertEquals(2, $this->show->podium_reveal_step);
        $this->assertFalse($this->show->winner_revealed);
    }

    public function test_restart_clears_podium_reveal_step(): void
    {
        $this->completeAllVotingWithDistinctScores();
        $control = app(TalentShowControlService::class);
        $control->showRanking($this->show->fresh());
        $control->startPodiumReveal($this->show->fresh());

        $this->assertEquals(1, $this->show->fresh()->podium_reveal_step);

        $control->restartShow($this->show->fresh());
        $this->show->refresh();

        $this->assertEquals(0, $this->show->podium_reveal_step);
        $this->assertFalse($this->show->winner_revealed);
    }

    public function test_winner_monitor_shows_waiting_before_podium(): void
    {
        $this->completeAllVotingWithDistinctScores();
        app(TalentShowControlService::class)->showRanking($this->show->fresh());

        $this->get(route('presentation.show'))
            ->assertOk()
            ->assertSee('Αναμονή αποκάλυψης top 5');
    }

    public function test_final_overview_shows_ranking_without_chart(): void
    {
        $this->completeAllVotingWithDistinctScores();
        $control = app(TalentShowControlService::class);
        $control->showRanking($this->show->fresh());
        $control->startPodiumReveal($this->show->fresh());
        $control->nextPodiumReveal($this->show->fresh());
        $control->nextPodiumReveal($this->show->fresh());

        $this->assertTrue($control->canShowFinalOverview($this->show->fresh()));

        $control->showFinalOverview($this->show->fresh());
        $this->show->refresh();

        $this->assertTrue($this->show->show_final_overview);
        $this->assertFalse($this->show->show_final_chart);

        $this->get(route('presentation.show'))
            ->assertOk()
            ->assertSee('Τελική κατάταξη')
            ->assertSee('Team 1')
            ->assertDontSee('Γράφημα αποτελεσμάτων');

        $control->hideFinalOverview($this->show->fresh());
        $this->assertFalse($this->show->fresh()->show_final_overview);
    }

    public function test_final_chart_shows_chart_without_ranking_list(): void
    {
        $this->completeAllVotingWithDistinctScores();
        $control = app(TalentShowControlService::class);
        $control->showRanking($this->show->fresh());
        $control->startPodiumReveal($this->show->fresh());
        $control->nextPodiumReveal($this->show->fresh());
        $control->nextPodiumReveal($this->show->fresh());

        $control->showFinalChart($this->show->fresh());
        $this->show->refresh();

        $this->assertTrue($this->show->show_final_chart);
        $this->assertFalse($this->show->show_final_overview);

        $this->get(route('presentation.show'))
            ->assertOk()
            ->assertSee('Γράφημα αποτελεσμάτων')
            ->assertDontSee('ΝΙΚΗΤΡΙΑ');
    }

    public function test_scoreboard_shows_on_main_monitor(): void
    {
        $this->completeAllVotingWithDistinctScores();
        $control = app(TalentShowControlService::class);
        $control->showRanking($this->show->fresh());
        $control->startPodiumReveal($this->show->fresh());
        $control->nextPodiumReveal($this->show->fresh());
        $control->nextPodiumReveal($this->show->fresh());

        $this->assertTrue($control->canShowScoreboardPanel($this->show->fresh()));

        $control->showScoreboard($this->show->fresh());
        $this->show->refresh();

        $this->assertTrue($this->show->show_scoreboard);
        $this->assertFalse($this->show->show_final_overview);
        $this->assertFalse($this->show->show_final_chart);

        $this->get(route('presentation.show'))
            ->assertOk()
            ->assertSee('Πίνακας βαθμολογιών')
            ->assertSee('Team 1');

        $control->hideScoreboard($this->show->fresh());
        $this->assertFalse($this->show->fresh()->show_scoreboard);
    }

    public function test_cannot_show_final_overview_before_podium_complete(): void
    {
        $this->completeAllVotingWithDistinctScores();
        $control = app(TalentShowControlService::class);
        $control->showRanking($this->show->fresh());

        $this->assertFalse($control->canShowFinalOverview($this->show->fresh()));

        $this->expectException(InvalidArgumentException::class);
        $control->showFinalOverview($this->show->fresh());
    }

    public function test_presentation_background_upload_and_remove(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->image('bg.jpg', 800, 600);

        $control = app(TalentShowControlService::class);
        $control->storePresentationBackground($this->show->fresh(), $file);
        $this->show->refresh();

        $this->assertEquals('image', $this->show->presentation_bg_type);
        $this->assertNotNull($this->show->presentation_bg_path);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($this->show->presentation_bg_path));
        $this->assertStringContainsString('/media/', $this->show->presentationBackgroundUrl());

        $this->get($this->show->presentationBackgroundUrl())->assertOk();

        $control->removePresentationBackground($this->show->fresh());
        $this->show->refresh();

        $this->assertNull($this->show->presentation_bg_path);
        $this->assertNull($this->show->presentation_bg_type);
    }

    public function test_presentation_background_accepts_video_by_extension(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('bg.mp4', 1024, 'application/octet-stream');

        app(TalentShowControlService::class)->storePresentationBackground($this->show->fresh(), $file);
        $this->show->refresh();

        $this->assertEquals('video', $this->show->presentation_bg_type);
        $this->get(route('media.public', ['path' => $this->show->presentation_bg_path]))->assertOk();
    }

    public function test_restart_clears_final_overview_flag(): void
    {
        $this->completeAllVotingWithDistinctScores();
        $control = app(TalentShowControlService::class);
        $control->showRanking($this->show->fresh());
        $control->startPodiumReveal($this->show->fresh());
        $control->nextPodiumReveal($this->show->fresh());
        $control->nextPodiumReveal($this->show->fresh());
        $control->showFinalOverview($this->show->fresh());
        $control->showFinalChart($this->show->fresh());

        $this->assertTrue($this->show->fresh()->show_final_chart);
        $this->assertFalse($this->show->fresh()->show_final_overview);

        $control->restartShow($this->show->fresh());
        $this->assertFalse($this->show->fresh()->show_final_overview);
        $this->assertFalse($this->show->fresh()->show_final_chart);
        $this->assertFalse($this->show->fresh()->show_scoreboard);
    }
}
