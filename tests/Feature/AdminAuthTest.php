<?php

namespace Tests\Feature;

use App\Enums\TalentShowStatus;
use App\Models\Judge;
use App\Models\JudgeSession;
use App\Models\Team;
use App\Models\Vote;
use App\Services\JudgeAccessService;
use App\Services\ResultsService;
use App\Services\ScoreCalculationService;
use App\Services\TalentShowControlService;
use App\Services\VoteService;
use Illuminate\Support\Facades\Hash;
use Tests\TalentShowTestCase;

class AdminAuthTest extends TalentShowTestCase
{
    public function test_admin_can_login(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_guest_cannot_access_admin_routes(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }
}
