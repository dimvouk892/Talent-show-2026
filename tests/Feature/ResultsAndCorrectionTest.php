<?php

namespace Tests\Feature;

use App\Models\Vote;
use App\Services\ResultsService;
use App\Services\TalentShowControlService;
use App\Services\VoteService;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TalentShowTestCase;

class ResultsAndCorrectionTest extends TalentShowTestCase
{
    protected function completeAllVoting(): void
    {
        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show);
        $teams = $this->show->activeTeams()->ordered()->get();
        $scores = [9, 10, 12];

        foreach ($teams as $index => $team) {
            $this->show->refresh();
            $score = $scores[$index % 3];
            foreach ($this->show->judges as $judge) {
                app(VoteService::class)->submit($judge, $this->show->currentTeam, $score);
            }
            $control->nextTeam($this->show->fresh());
        }
    }

    public function test_admin_can_submit_vote_on_behalf_of_judge(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        $team = $this->show->currentTeam;

        $vote = app(VoteService::class)->submitOnBehalf(
            $judge,
            $team,
            12,
            'Κινητό κριτή δεν συνδέεται',
            $this->admin,
        );

        $this->assertDatabaseHas('votes', [
            'id' => $vote->id,
            'judge_id' => $judge->id,
            'team_id' => $team->id,
            'score' => 12,
            'is_admin_edited' => true,
            'edited_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'vote_submitted_by_admin',
            'entity_type' => 'vote',
        ]);
    }

    public function test_admin_proxy_vote_via_live_control_unlocks_next_team(): void
    {
        $this->openScoring();
        $judges = $this->show->judges;
        $team = $this->show->currentTeam;

        foreach ($judges->take(4) as $judge) {
            app(VoteService::class)->submit($judge, $team, 10);
        }

        $missing = $judges->skip(4)->first();

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\LiveControl::class, ['talentShow' => $this->show])
            ->call('openProxyVote', $missing->id)
            ->set('correctionScore', 9)
            ->set('correctionReason', 'Πρόβλημα με το κινητό του κριτή')
            ->call('correctVote')
            ->assertSet('flashSuccess', 'Καταχωρίστηκε βαθμός για '.$missing->name.'.');

        $scores = app(\App\Services\ScoreCalculationService::class)->forTeam($team->fresh(), $this->show->fresh());
        $this->assertTrue($scores['is_complete']);
    }

    public function test_admin_correction_requires_reason(): void
    {
        $this->openScoring();
        $vote = Vote::create([
            'talent_show_id' => $this->show->id,
            'team_id' => $this->show->current_team_id,
            'judge_id' => $this->show->judges()->first()->id,
            'score' => 9,
            'submitted_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(VoteService::class)->correct($vote, 10, 'bad', $this->admin);
    }

    public function test_admin_correction_creates_revision(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        $vote = app(VoteService::class)->submit($judge, $this->show->currentTeam, 9);

        app(VoteService::class)->correct($vote, 12, 'Διόρθωση λάθους', $this->admin);

        $this->assertDatabaseHas('vote_revisions', [
            'vote_id' => $vote->id,
            'old_score' => 9,
            'new_score' => 12,
        ]);
    }

    public function test_admin_correction_creates_audit_log(): void
    {
        $this->openScoring();
        $vote = app(VoteService::class)->submit(
            $this->show->judges()->first(),
            $this->show->currentTeam,
            9
        );

        app(VoteService::class)->correct($vote, 10, 'Διόρθωση λάθους', $this->admin);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'vote_corrected',
            'entity_type' => 'vote',
        ]);
    }

    public function test_admin_correction_changes_ranking(): void
    {
        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show);

        $teams = $this->show->activeTeams()->ordered()->take(2)->get();

        foreach ($this->show->judges as $judge) {
            app(VoteService::class)->submit($judge, $teams[0], 10);
        }
        $control->nextTeam($this->show->fresh());
        $team2 = $this->show->fresh()->currentTeam;
        $this->assertNotNull($team2);
        $this->assertEquals($teams[1]->id, $team2->id);

        foreach ($this->show->judges as $judge) {
            app(VoteService::class)->submit($judge, $team2, 9);
        }

        $rankingBefore = app(ResultsService::class)->getRanking($this->show->fresh());
        $secondTeamScore = collect($rankingBefore)->firstWhere('team.id', $teams[1]->id)['total_score'];

        $vote = Vote::where('team_id', $teams[1]->id)->first();
        app(VoteService::class)->correct($vote, 12, 'Διόρθωση σφάλματος', $this->admin);

        $rankingAfter = app(ResultsService::class)->getRanking($this->show->fresh());
        $newSecondTeamScore = collect($rankingAfter)->firstWhere('team.id', $teams[1]->id)['total_score'];

        $this->assertGreaterThan($secondTeamScore, $newSecondTeamScore);
    }

    public function test_winner_not_shown_before_reveal(): void
    {
        $this->completeAllVoting();
        $this->show->refresh();

        $winner = app(ResultsService::class)->getWinner($this->show);
        $this->assertNull($winner);
    }

    public function test_ranking_shown_only_when_allowed(): void
    {
        $this->completeAllVoting();
        $this->show->refresh();

        $this->get(route('presentation.ranking'))
            ->assertSee('δεν είναι ακόμα διαθέσιμη');

        app(TalentShowControlService::class)->showRanking($this->show->fresh());

        $this->get(route('presentation.ranking'))
            ->assertSee('Τελική κατάταξη');
    }

    public function test_tiebreak_with_twelves_works(): void
    {
        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show);
        $teams = $this->show->activeTeams()->ordered()->take(2)->get();

        // Both total 52; team1 has more 12s
        $scores1 = [12, 12, 10, 9, 9];
        $scores2 = [12, 10, 10, 10, 10];

        foreach ($this->show->judges as $i => $judge) {
            app(VoteService::class)->submit($judge, $teams[0], $scores1[$i]);
        }
        $control->nextTeam($this->show->fresh());
        $this->show->refresh();

        foreach ($this->show->judges as $i => $judge) {
            app(VoteService::class)->submit($judge, $this->show->currentTeam, $scores2[$i]);
        }

        $ranking = app(ResultsService::class)->getRanking($this->show->fresh());
        $this->assertEquals($teams[0]->id, $ranking[0]['team']->id);
    }

    public function test_partial_results_are_listed_before_all_judges_vote(): void
    {
        $this->openScoring();
        $team = $this->show->currentTeam;
        app(VoteService::class)->submit($this->show->judges()->first(), $team, 10);

        $ranking = app(ResultsService::class)->getRanking($this->show->fresh());

        $this->assertCount(1, $ranking);
        $this->assertFalse($ranking[0]['is_complete']);
        $this->assertNull($ranking[0]['ranking_position']);
        $this->assertEquals(10, $ranking[0]['total_score']);
    }

    public function test_manual_tie_selection_works(): void
    {
        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show);
        $teams = $this->show->activeTeams()->ordered()->take(2)->get();

        foreach ($this->show->judges as $judge) {
            app(VoteService::class)->submit($judge, $teams[0], 10);
        }
        $control->nextTeam($this->show->fresh());
        $this->show->refresh();

        foreach ($this->show->judges as $judge) {
            app(VoteService::class)->submit($judge, $this->show->currentTeam, 10);
        }

        $control->closeScoring($this->show->fresh());
        $control->showRanking($this->show->fresh());

        $this->expectException(InvalidArgumentException::class);
        $control->revealWinner($this->show->fresh());

        $control->revealWinner($this->show->fresh(), $teams[1]->id);
        $this->show->refresh();

        $this->assertEquals($teams[1]->id, $this->show->winner_team_id);
        $this->assertTrue($this->show->winner_revealed);
    }

    public function test_final_vote_required_before_ranking(): void
    {
        $finalJudge = $this->show->judges()->skip(4)->first();
        $finalJudge->update(['is_final_voter' => true]);

        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show);
        $teams = $this->show->activeTeams()->ordered()->get();
        $scoringJudges = $this->show->scoringJudges()->get();

        foreach ($teams as $index => $team) {
            $this->show->refresh();
            foreach ($scoringJudges as $judge) {
                app(VoteService::class)->submit($judge, $this->show->currentTeam, 10);
            }
            $control->nextTeam($this->show->fresh());
        }

        $this->show->refresh();
        $this->assertTrue($this->show->final_vote_open);
        $this->assertTrue($this->show->hasPendingFinalVote());
        $this->assertFalse($control->canShowRanking($this->show));

        $this->expectException(InvalidArgumentException::class);
        $control->showRanking($this->show->fresh());
    }

    public function test_final_vote_unlocks_ranking(): void
    {
        $finalJudge = $this->show->judges()->skip(4)->first();
        $finalJudge->update(['is_final_voter' => true]);

        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show);
        $teams = $this->show->activeTeams()->ordered()->get();
        $scoringJudges = $this->show->scoringJudges()->get();

        foreach ($teams as $team) {
            $this->show->refresh();
            foreach ($scoringJudges as $judge) {
                app(VoteService::class)->submit($judge, $this->show->currentTeam, 10);
            }
            $control->nextTeam($this->show->fresh());
        }

        $chosen = $teams->first();
        app(VoteService::class)->submitFinalVote($finalJudge->fresh(), $chosen, 12);

        $this->show->refresh();
        $this->assertFalse($this->show->hasPendingFinalVote());
        $this->assertTrue($control->canShowRanking($this->show));

        $ranking = app(ResultsService::class)->getRanking($this->show);
        $top = collect($ranking)->firstWhere('team.id', $chosen->id);
        $this->assertEquals(40 + 12, $top['total_score']); // 4 scoring judges * 10 + final 12
    }

    public function test_admin_can_correct_final_vote_team_and_score(): void
    {
        $finalJudge = $this->show->judges()->skip(4)->first();
        $finalJudge->update(['is_final_voter' => true]);

        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show);
        $teams = $this->show->activeTeams()->ordered()->get();
        $scoringJudges = $this->show->scoringJudges()->get();

        foreach ($teams as $team) {
            $this->show->refresh();
            foreach ($scoringJudges as $judge) {
                app(VoteService::class)->submit($judge, $this->show->currentTeam, 10);
            }
            $control->nextTeam($this->show->fresh());
        }

        $vote = app(VoteService::class)->submitFinalVote($finalJudge->fresh(), $teams[0], 9);

        app(VoteService::class)->correctFinalVote(
            $vote,
            $teams[1],
            12,
            'Διόρθωση τελικής ψήφου από admin',
            $this->admin,
        );

        $vote->refresh();
        $this->assertEquals($teams[1]->id, $vote->team_id);
        $this->assertEquals(12, $vote->score);
        $this->assertTrue($vote->is_admin_edited);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'final_vote_corrected',
            'entity_type' => 'vote',
        ]);
    }

    public function test_admin_can_edit_score_from_results_table(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        $team = $this->show->currentTeam;

        app(VoteService::class)->submit($judge, $team, 9);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\Results::class, ['talentShow' => $this->show])
            ->call('openCellEdit', $team->id, $judge->id)
            ->set('cellScore', 12)
            ->set('cellReason', 'Διόρθωση από πίνακα αποτελεσμάτων')
            ->call('saveCellScore')
            ->assertSet('flashSuccess', 'Η βαθμολογία διορθώθηκε.');

        $this->assertDatabaseHas('votes', [
            'judge_id' => $judge->id,
            'team_id' => $team->id,
            'score' => 12,
            'is_admin_edited' => true,
        ]);
    }

    public function test_admin_can_add_missing_score_from_results_table_for_past_team(): void
    {
        $control = app(TalentShowControlService::class);
        $this->openScoring();
        $teams = $this->show->activeTeams()->ordered()->get();
        $judges = $this->show->scoringJudges()->get();

        foreach ($judges as $judge) {
            app(VoteService::class)->submit($judge, $this->show->currentTeam, 10);
        }
        $control->nextTeam($this->show->fresh());

        $firstTeam = $teams->first();
        $missingJudge = $judges->first();
        Vote::where('team_id', $firstTeam->id)->where('judge_id', $missingJudge->id)->delete();

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\Results::class, ['talentShow' => $this->show->fresh()])
            ->call('openCellEdit', $firstTeam->id, $missingJudge->id)
            ->set('cellScore', 9)
            ->set('cellReason', 'Καταχώρηση από πίνακα αποτελεσμάτων')
            ->call('saveCellScore')
            ->assertSet('flashSuccess', 'Καταχωρίστηκε βαθμός για '.$missingJudge->name.'.');

        $this->assertDatabaseHas('votes', [
            'judge_id' => $missingJudge->id,
            'team_id' => $firstTeam->id,
            'score' => 9,
            'is_admin_edited' => true,
        ]);
    }
}
