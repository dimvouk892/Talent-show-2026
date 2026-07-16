<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Models\AuditLog;
use App\Models\TalentShow;
use App\Models\Vote;
use App\Services\VoteService;
use Livewire\Livewire;
use Tests\TalentShowTestCase;

class DashboardTalentShowTest extends TalentShowTestCase
{
    public function test_dashboard_shows_edit_link(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->assertSee(route('admin.talent-shows.edit', $this->show, false));
    }

    public function test_dashboard_can_delete_talent_show(): void
    {
        $showId = $this->show->id;

        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->call('askDelete', $showId)
            ->assertSet('confirmDeleteId', $showId)
            ->call('confirmDelete')
            ->assertSet('flashSuccess', 'Το Talent Show «'.$this->show->title.'» διαγράφηκε.');

        $this->assertNull(TalentShow::find($showId));
    }

    public function test_dashboard_can_delete_show_with_votes(): void
    {
        $this->openScoring();
        app(VoteService::class)->submit($this->show->judges()->first(), $this->show->currentTeam, 10);

        $showId = $this->show->id;

        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->call('confirmDelete')
            ->assertSet('confirmDeleteId', null);

        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->call('askDelete', $showId)
            ->call('confirmDelete')
            ->assertSet('flashSuccess', 'Το Talent Show «'.$this->show->title.'» διαγράφηκε.');

        $this->assertEquals(0, Vote::where('talent_show_id', $showId)->count());
        $this->assertNull(TalentShow::find($showId));
    }

    public function test_delete_clears_audit_logs_for_show(): void
    {
        AuditLog::create([
            'action' => 'test',
            'entity_type' => 'talent_show',
            'entity_id' => $this->show->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->call('askDelete', $this->show->id)
            ->call('confirmDelete');

        $this->assertEquals(0, AuditLog::where('entity_type', 'talent_show')->where('entity_id', $this->show->id)->count());
    }
}
