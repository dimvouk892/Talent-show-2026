<?php

namespace Tests;

use App\Enums\TalentShowStatus;
use App\Enums\TeamStatus;
use App\Models\Judge;
use App\Models\JudgeSession;
use App\Models\TalentShow;
use App\Models\Team;
use App\Models\User;
use App\Models\Vote;
use App\Services\JudgeAccessService;
use App\Services\ResultsService;
use App\Services\ScoreCalculationService;
use App\Services\TalentShowControlService;
use App\Services\VoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class TalentShowTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected TalentShow $show;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->regularUser = User::factory()->create(['role' => 'user']);
        $this->show = $this->createShowWithTeamsAndJudges();
    }

    protected function createShowWithTeamsAndJudges(int $teamCount = 3): TalentShow
    {
        $show = TalentShow::create([
            'title' => 'Test Show',
            'slug' => 'test-show-'.Str::random(6),
            'status' => TalentShowStatus::Draft,
            'created_by' => $this->admin->id,
        ]);

        for ($i = 1; $i <= $teamCount; $i++) {
            Team::create([
                'talent_show_id' => $show->id,
                'name' => "Team {$i}",
                'display_order' => $i,
                'is_active' => true,
            ]);
        }

        for ($i = 1; $i <= 5; $i++) {
            Judge::create([
                'talent_show_id' => $show->id,
                'name' => "Judge {$i}",
                'display_order' => $i,
                'is_active' => true,
            ]);
        }

        return $show->fresh();
    }

    protected function generateQrToken(Judge $judge): string
    {
        return app(JudgeAccessService::class)->generateQrToken($judge);
    }

    protected function loginJudge(Judge $judge): void
    {
        $token = $this->generateQrToken($judge);
        $this->get(route('judge.access', ['judge' => $judge, 'token' => $token]));
    }

    protected function judgeVoteUrl(Judge $judge): string
    {
        return route('judge.vote', $judge);
    }

    protected function openScoring(): void
    {
        app(TalentShowControlService::class)->openScoring($this->show->fresh());
        $this->show->refresh();
    }
}
