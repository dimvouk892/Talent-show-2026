<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\Vote;
use App\Models\VoteRevision;
use App\Services\ResultsService;
use App\Services\ScoreCalculationService;
use App\Services\TalentShowControlService;
use App\Services\VoteService;
use InvalidArgumentException;
use Tests\TalentShowTestCase;

class ResultsAndCorrectionTest extends TalentShowTestCase
{
    protected function completeAllVoting(): void
    {
        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show);
        $teams = $this->show->activeTeams()->ordered()->get();

        foreach ($teams as $index => $team) {
            $this->show->refresh();
            $score = 7 + $index;
            foreach ($this->show->judges as $judge) {
                app(VoteService::class)->submit($judge, $this->show->currentTeam, min(10, $score));
            }
            $control->nextTeam($this->show->fresh());
        }
    }

    public function test_admin_correction_requires_reason(): void
    {
        $this->openScoring();
        $vote = Vote::create([
            'talent_show_id' => $this->show->id,
            'team_id' => $this->show->current_team_id,
            'judge_id' => $this->show->judges()->first()->id,
            'score' => 5,
            'submitted_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(VoteService::class)->correct($vote, 8, 'bad', $this->admin);
    }

    public function test_admin_correction_creates_revision(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        $vote = app(VoteService::class)->submit($judge, $this->show->currentTeam, 5);

        app(VoteService::class)->correct($vote, 8, 'Διόρθωση λάθους', $this->admin);

        $this->assertDatabaseHas('vote_revisions', [
            'vote_id' => $vote->id,
            'old_score' => 5,
            'new_score' => 8,
        ]);
    }

    public function test_admin_correction_creates_audit_log(): void
    {
        $this->openScoring();
        $vote = app(VoteService::class)->submit(
            $this->show->judges()->first(),
            $this->show->currentTeam,
            5
        );

        app(VoteService::class)->correct($vote, 8, 'Διόρθωση λάθους', $this->admin);

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
            app(VoteService::class)->submit($judge, $teams[0], 8);
        }
        $control->nextTeam($this->show->fresh());
        $team2 = $this->show->fresh()->currentTeam;
        $this->assertNotNull($team2);
        $this->assertEquals($teams[1]->id, $team2->id);

        foreach ($this->show->judges as $judge) {
            app(VoteService::class)->submit($judge, $team2, 7);
        }

        $rankingBefore = app(ResultsService::class)->getRanking($this->show->fresh());
        $secondTeamScore = collect($rankingBefore)->firstWhere('team.id', $teams[1]->id)['total_score'];

        $vote = Vote::where('team_id', $teams[1]->id)->first();
        app(VoteService::class)->correct($vote, 10, 'Διόρθωση σφάλματος', $this->admin);

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

        $this->get(route('presentation.ranking', $this->show))
            ->assertSee('δεν είναι ακόμα διαθέσιμη');

        app(TalentShowControlService::class)->showRanking($this->show->fresh());

        $this->get(route('presentation.ranking', $this->show->fresh()))
            ->assertSee('Τελική κατάταξη');
    }

    public function test_tiebreak_with_tens_works(): void
    {
        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show);
        $teams = $this->show->activeTeams()->ordered()->take(2)->get();

        $scores1 = [10, 10, 10, 10, 2];
        $scores2 = [9, 9, 9, 9, 6];

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

    public function test_tiebreak_with_nines_works(): void
    {
        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show);
        $teams = $this->show->activeTeams()->ordered()->take(2)->get();

        foreach ($this->show->judges as $judge) {
            app(VoteService::class)->submit($judge, $teams[0], 9);
        }
        $control->nextTeam($this->show->fresh());
        $this->show->refresh();

        $ninesAndOne = [9, 9, 9, 9, 8];
        foreach ($this->show->judges as $i => $judge) {
            app(VoteService::class)->submit($judge, $this->show->currentTeam, $ninesAndOne[$i]);
        }

        $ranking = app(ResultsService::class)->getRanking($this->show->fresh());
        $this->assertEquals($teams[0]->id, $ranking[0]['team']->id);
    }

    public function test_partial_results_are_listed_before_all_judges_vote(): void
    {
        $this->openScoring();
        $team = $this->show->currentTeam;
        app(VoteService::class)->submit($this->show->judges()->first(), $team, 8);

        $ranking = app(ResultsService::class)->getRanking($this->show->fresh());

        $this->assertCount(1, $ranking);
        $this->assertFalse($ranking[0]['is_complete']);
        $this->assertNull($ranking[0]['ranking_position']);
        $this->assertEquals(8, $ranking[0]['total_score']);
    }

    public function test_manual_tie_selection_works(): void
    {
        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show);
        $teams = $this->show->activeTeams()->ordered()->take(2)->get();

        foreach ($this->show->judges as $judge) {
            app(VoteService::class)->submit($judge, $teams[0], 8);
        }
        $control->nextTeam($this->show->fresh());
        $this->show->refresh();

        foreach ($this->show->judges as $judge) {
            app(VoteService::class)->submit($judge, $this->show->currentTeam, 8);
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
}
