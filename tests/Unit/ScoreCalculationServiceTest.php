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

    public function test_maximum_score_equals_active_judges_times_ten(): void
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
        $this->assertEquals(50, $result['maximum_score']);
    }
}
