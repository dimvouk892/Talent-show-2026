<?php

namespace Tests\Feature;

use App\Livewire\Admin\Teams\Index as TeamsIndex;
use App\Models\Team;
use App\Services\JudgeAccessService;
use App\Services\QrCodeService;
use App\Services\VoteService;
use Livewire\Livewire;
use Tests\TalentShowTestCase;

class TeamsAdminTest extends TalentShowTestCase
{
    public function test_admin_can_create_edit_and_delete_team(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeamsIndex::class, ['talentShow' => $this->show])
            ->call('openCreate')
            ->set('name', 'Νέα Ομάδα')
            ->set('display_order', 99)
            ->call('save');

        $team = Team::where('talent_show_id', $this->show->id)->where('name', 'Νέα Ομάδα')->first();
        $this->assertNotNull($team);
        $this->assertSame(99, $team->display_order);

        Livewire::actingAs($this->admin)
            ->test(TeamsIndex::class, ['talentShow' => $this->show])
            ->call('edit', $team->id)
            ->set('name', 'Ομάδα Ενημερωμένη')
            ->call('save');

        $this->assertEquals('Ομάδα Ενημερωμένη', $team->fresh()->name);

        Livewire::actingAs($this->admin)
            ->test(TeamsIndex::class, ['talentShow' => $this->show])
            ->call('delete', $team->id);

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_admin_cannot_delete_team_with_votes_via_ui(): void
    {
        $this->openScoring();
        $team = $this->show->currentTeam;
        app(VoteService::class)->submit($this->show->judges()->first(), $team, 10);

        Livewire::actingAs($this->admin)
            ->test(TeamsIndex::class, ['talentShow' => $this->show])
            ->call('delete', $team->id);

        $this->assertDatabaseHas('teams', ['id' => $team->id]);
        $this->assertTrue($team->fresh()->hasVotes());
    }

    public function test_judge_access_links_login_and_open_vote_panel(): void
    {
        $judge = $this->show->judges()->first();
        $token = app(JudgeAccessService::class)->generateQrToken($judge);
        $url = app(QrCodeService::class)->accessUrl($judge, $token);

        $this->assertStringContainsString('/judge/access/'.$judge->id.'/', $url);

        $this->get($url)
            ->assertRedirect(route('judge.vote', $judge));

        $this->get(route('judge.vote', $judge))
            ->assertOk()
            ->assertSee($judge->name);
    }
}
