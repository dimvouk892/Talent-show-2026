<?php

namespace Tests\Feature;

use App\Enums\TalentShowStatus;
use App\Enums\TeamStatus;
use App\Models\AuditLog;
use App\Models\Judge;
use App\Models\Team;
use App\Models\Vote;
use App\Models\VoteRevision;
use App\Services\ResultsService;
use App\Services\ScoreCalculationService;
use App\Services\TalentShowControlService;
use App\Services\VoteService;
use App\Livewire\Admin\LiveControl;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TalentShowTestCase;

class TalentShowControlTest extends TalentShowTestCase
{
    protected function voteForCurrentTeam(): void
    {
        $team = $this->show->currentTeam;
        foreach ($this->show->judges as $judge) {
            app(VoteService::class)->submit($judge, $team, 8);
        }
    }

    public function test_reveal_scores_works_with_partial_votes(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        app(VoteService::class)->submit($judge, $this->show->currentTeam, 7);

        $this->show->update(['show_live_scores' => false]);

        app(TalentShowControlService::class)->revealScores($this->show->fresh());

        $this->assertTrue($this->show->fresh()->show_live_scores);
    }

    public function test_first_vote_enables_live_scores_on_presentation(): void
    {
        $this->openScoring();
        $this->assertFalse($this->show->show_live_scores);

        app(VoteService::class)->submit($this->show->judges()->first(), $this->show->currentTeam, 8);

        $this->assertTrue($this->show->fresh()->show_live_scores);
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
        app(VoteService::class)->submit($judge, $this->show->currentTeam, 7);

        $this->expectException(InvalidArgumentException::class);
        app(TalentShowControlService::class)->nextTeam($this->show);
    }

    public function test_cannot_proceed_during_team_intro(): void
    {
        $team = $this->show->teams()->ordered()->first();
        $team->update(['video_path' => 'teams/'.$this->show->id.'/videos/intro.mp4']);

        app(TalentShowControlService::class)->openScoring($this->show->fresh());

        $this->assertFalse(app(TalentShowControlService::class)->canProceedToNext($this->show->fresh()));
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

        foreach ($teams as $index => $team) {
            $this->show->refresh();
            foreach ($this->show->judges as $judge) {
                app(VoteService::class)->submit($judge, $this->show->currentTeam, 7 + $index);
            }
            if ($index < $teams->count() - 1) {
                $control->nextTeam($this->show->fresh());
            } else {
                $control->nextTeam($this->show->fresh());
            }
        }

        $this->show->refresh();
        $this->assertEquals(TalentShowStatus::ScoringClosed, $this->show->status);
        $this->assertNull($this->show->current_team_id);
    }

    public function test_total_score_calculated_correctly(): void
    {
        $this->openScoring();
        $team = $this->show->currentTeam;
        $scores = [8, 9, 7, 8, 9];

        foreach ($this->show->judges as $i => $judge) {
            app(VoteService::class)->submit($judge, $team, $scores[$i]);
        }

        $result = app(ScoreCalculationService::class)->forTeam($team->fresh());
        $this->assertEquals(41, $result['total_score']);
    }

    public function test_average_score_calculated_correctly(): void
    {
        $this->openScoring();
        $team = $this->show->currentTeam;

        foreach ($this->show->judges as $judge) {
            app(VoteService::class)->submit($judge, $team, 8);
        }

        $result = app(ScoreCalculationService::class)->forTeam($team->fresh());
        $this->assertEquals(8.0, $result['average_score']);
    }

    public function test_maximum_score_is_correct(): void
    {
        $this->openScoring();
        $result = app(ScoreCalculationService::class)->forTeam($this->show->currentTeam);
        $this->assertEquals(50, $result['maximum_score']);
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
        app(VoteService::class)->submit($this->show->judges()->first(), $team, 5);

        $this->assertTrue($team->hasVotes());
    }

    public function test_cannot_delete_judge_with_votes(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        app(VoteService::class)->submit($judge, $this->show->currentTeam, 5);

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

    public function test_clear_scores_via_live_control_modal(): void
    {
        $this->openScoring();
        app(VoteService::class)->submit($this->show->judges()->first(), $this->show->currentTeam, 8);

        Livewire::actingAs($this->admin)
            ->test(LiveControl::class, ['talentShow' => $this->show])
            ->call('askClearScores')
            ->assertSet('showClearScoresConfirm', true)
            ->call('confirmClearScores')
            ->assertSet('flashSuccess', 'Οι βαθμολογίες διαγράφηκαν. Η εκδήλωση επανήλθε σε κατάσταση «Έτοιμο».')
            ->assertSet('showClearScoresConfirm', false);

        $this->assertEquals(0, Vote::where('talent_show_id', $this->show->id)->count());
        $this->assertEquals(TalentShowStatus::Ready, $this->show->fresh()->status);
    }

    public function test_restart_show_allows_judges_to_vote_again(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        app(VoteService::class)->submit($judge, $this->show->currentTeam, 8);

        app(TalentShowControlService::class)->restartShow($this->show->fresh());

        $this->show->refresh();
        $team = $this->show->currentTeam;

        $vote = app(VoteService::class)->submit($judge, $team, 7);

        $this->assertEquals(7, $vote->score);
        $this->assertEquals(1, Vote::where('talent_show_id', $this->show->id)->count());
    }

    public function test_live_control_buttons_show_feedback_in_component(): void
    {
        Livewire::actingAs($this->admin)
            ->test(LiveControl::class, ['talentShow' => $this->show])
            ->call('openScoring')
            ->assertSet('flashSuccess', 'Η βαθμολόγηση άνοιξε.')
            ->assertSee('Η βαθμολόγηση άνοιξε.');
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

    public function test_restart_via_live_control_modal(): void
    {
        $this->openScoring();
        $this->voteForCurrentTeam();

        Livewire::actingAs($this->admin)
            ->test(LiveControl::class, ['talentShow' => $this->show])
            ->call('confirmRestart')
            ->assertSet('showRestartConfirm', true)
            ->call('restartShow')
            ->assertSet('flashSuccess', 'Η εκδήλωση επανεκκινήθηκε και άνοιξε η βαθμολόγηση από την 1η ομάδα.')
            ->assertSet('showRestartConfirm', false);

        $this->show->refresh();
        $this->assertEquals(0, Vote::where('talent_show_id', $this->show->id)->count());
        $this->assertEquals(TalentShowStatus::ScoringOpen, $this->show->status);
    }

    public function test_restart_works_with_any_number_of_active_judges(): void
    {
        $this->openScoring();
        app(VoteService::class)->submit($this->show->judges()->first(), $this->show->currentTeam, 8);

        $this->show->judges()->update(['is_active' => false]);
        $this->show->judges()->limit(2)->update(['is_active' => true]);

        app(TalentShowControlService::class)->restartShow($this->show->fresh());

        $this->assertEquals(0, Vote::where('talent_show_id', $this->show->id)->count());
        $this->assertEquals(TalentShowStatus::ScoringOpen, $this->show->fresh()->status);
    }
}
