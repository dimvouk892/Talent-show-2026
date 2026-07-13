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
use InvalidArgumentException;
use Livewire\Livewire;
use App\Livewire\Judge\VotePanel;
use Tests\TalentShowTestCase;

class VotingTest extends TalentShowTestCase
{
    public function test_judge_sees_only_active_team(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        $this->loginJudge($judge);

        $this->get($this->judgeVoteUrl($judge))
            ->assertSee($this->show->currentTeam->name);
    }

    public function test_judge_cannot_see_other_judges_scores(): void
    {
        $this->openScoring();
        $judges = $this->show->judges;
        $team = $this->show->currentTeam;

        foreach ($judges as $judge) {
            app(VoteService::class)->submit($judge, $team, 8);
        }

        $this->loginJudge($judges->first());
        $response = $this->get($this->judgeVoteUrl($judges->first()));
        $response->assertDontSee('Συνολικό σκορ', false);
        $response->assertDontSee('Κριτής 2', false);
        $response->assertDontSee('Μέσος όρος', false);
    }

    public function test_judge_cannot_see_ranking(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        $this->loginJudge($judge);

        $this->get($this->judgeVoteUrl($judge))
            ->assertDontSee('κατάταξη')
            ->assertDontSee('ΝΙΚΗΤΡΙΑ');
    }

    public function test_score_below_one_rejected(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();

        $this->expectException(InvalidArgumentException::class);
        app(VoteService::class)->submit($judge, $this->show->currentTeam, 0);
    }

    public function test_score_above_ten_rejected(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();

        $this->expectException(InvalidArgumentException::class);
        app(VoteService::class)->submit($judge, $this->show->currentTeam, 11);
    }

    public function test_duplicate_vote_rejected(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        $team = $this->show->currentTeam;

        app(VoteService::class)->submit($judge, $team, 7);

        $this->expectException(InvalidArgumentException::class);
        app(VoteService::class)->submit($judge, $team, 8);
    }

    public function test_vote_for_inactive_team_rejected(): void
    {
        $this->openScoring();
        $inactiveTeam = Team::create([
            'talent_show_id' => $this->show->id,
            'name' => 'Inactive',
            'display_order' => 99,
            'is_active' => false,
        ]);
        $judge = $this->show->judges()->first();

        $this->expectException(InvalidArgumentException::class);
        app(VoteService::class)->submit($judge, $inactiveTeam, 5);
    }

    public function test_vote_when_show_closed_rejected(): void
    {
        $this->openScoring();
        $this->show->update(['status' => TalentShowStatus::ScoringClosed]);
        $judge = $this->show->judges()->first();

        $this->expectException(InvalidArgumentException::class);
        app(VoteService::class)->submit($judge, $this->show->currentTeam, 5);
    }

    public function test_vote_from_judge_of_other_show_rejected(): void
    {
        $otherShow = $this->createShowWithTeamsAndJudges(1);
        $this->openScoring();
        $otherJudge = $otherShow->judges()->first();

        $this->expectException(InvalidArgumentException::class);
        app(VoteService::class)->submit($otherJudge, $this->show->currentTeam, 5);
    }

    public function test_team_completes_only_after_all_active_judges_vote(): void
    {
        $this->openScoring();
        $team = $this->show->currentTeam;
        $judges = $this->show->judges;

        for ($i = 0; $i < 4; $i++) {
            app(VoteService::class)->submit($judges[$i], $team, 8);
        }

        $scores = app(ScoreCalculationService::class)->forTeam($team);
        $this->assertFalse($scores['is_complete']);

        app(VoteService::class)->submit($judges[4], $team, 9);
        $scores = app(ScoreCalculationService::class)->forTeam($team->fresh());
        $this->assertTrue($scores['is_complete']);
    }

    public function test_judges_can_vote_in_any_order_without_sequence(): void
    {
        $this->openScoring();
        $team = $this->show->currentTeam;
        $judges = $this->show->judges->reverse()->values();

        foreach ($judges as $index => $judge) {
            app(VoteService::class)->submit($judge, $team, 6 + $index);
        }

        $scores = app(ScoreCalculationService::class)->forTeam($team->fresh());
        $this->assertTrue($scores['is_complete']);
        $this->assertEquals(5, $scores['votes_count']);
    }
}
