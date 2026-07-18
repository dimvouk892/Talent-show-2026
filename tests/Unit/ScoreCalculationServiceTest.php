<?php

namespace Tests\Unit;

use App\Models\Judge;
use App\Models\TalentShow;
use App\Models\Team;
use App\Models\User;
use App\Services\ScoreCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_maximum_score_equals_active_judges_times_twelve(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $show = TalentShow::create([
            'title' => 'Unit Test Show',
            'slug' => 'unit-test',
            'created_by' => $admin->id,
        ]);

        $team = Team::create([
            'talent_show_id' => $show->id,
            'name' => 'Team A',
            'display_order' => 1,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Judge::create([
                'talent_show_id' => $show->id,
                'name' => "Judge {$i}",
                'is_active' => true,
            ]);
        }

        $service = app(ScoreCalculationService::class);
        $result = $service->forTeam($team, $show);

        $this->assertEquals(5, $result['active_judges_count']);
        $this->assertEquals(60, $result['maximum_score']);
    }

    public function test_total_includes_final_vote(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $show = TalentShow::create([
            'title' => 'Unit Test Show',
            'slug' => 'unit-avg-final',
            'created_by' => $admin->id,
        ]);

        $team = Team::create([
            'talent_show_id' => $show->id,
            'name' => 'Team A',
            'display_order' => 1,
            'is_active' => true,
        ]);

        $scoringJudges = [];
        for ($i = 1; $i <= 5; $i++) {
            $scoringJudges[] = Judge::create([
                'talent_show_id' => $show->id,
                'name' => "Judge {$i}",
                'display_order' => $i,
                'is_active' => true,
                'is_final_voter' => false,
            ]);
        }

        $finalJudge = Judge::create([
            'talent_show_id' => $show->id,
            'name' => 'Final',
            'display_order' => 6,
            'is_active' => true,
            'is_final_voter' => true,
        ]);

        foreach ($scoringJudges as $judge) {
            $team->votes()->create([
                'talent_show_id' => $show->id,
                'judge_id' => $judge->id,
                'score' => 10,
                'submitted_at' => now(),
            ]);
        }

        $team->votes()->create([
            'talent_show_id' => $show->id,
            'judge_id' => $finalJudge->id,
            'score' => 11,
            'submitted_at' => now(),
        ]);

        $result = app(ScoreCalculationService::class)->forTeam($team->fresh(), $show->fresh());

        $this->assertEquals(61, $result['total_score']);
        $this->assertEquals(71, $result['maximum_score']);
        $this->assertTrue($result['has_final_vote']);
        $this->assertEquals(11, $result['final_vote_score']);
    }
}
