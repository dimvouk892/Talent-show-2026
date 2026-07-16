<?php

namespace Tests\Feature;

use App\Livewire\Admin\AuditLogs;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use App\Services\TalentShowControlService;
use App\Services\VoteService;
use Livewire\Livewire;
use Tests\TalentShowTestCase;

class AuditLogsAdminTest extends TalentShowTestCase
{
    public function test_audit_logs_page_lists_show_related_entries(): void
    {
        app(TalentShowControlService::class)->startShow($this->show);

        Livewire::actingAs($this->admin)
            ->test(AuditLogs::class, ['talentShow' => $this->show])
            ->assertSee('show_started');
    }

    public function test_clear_history_deletes_all_show_audit_logs(): void
    {
        app(TalentShowControlService::class)->openScoring($this->show);
        $this->show->refresh();

        $vote = app(VoteService::class)->submit(
            $this->show->judges()->first(),
            $this->show->currentTeam,
            9,
        );
        app(VoteService::class)->correct($vote, 12, 'Διόρθωση λάθους', $this->admin);

        $this->assertGreaterThan(0, app(AuditLogService::class)->queryForTalentShow($this->show)->count());

        $deleted = app(AuditLogService::class)->clearForTalentShow($this->show);

        $this->assertGreaterThan(0, $deleted);
        $this->assertEquals(0, AuditLog::count());
    }

    public function test_clear_history_via_livewire_modal(): void
    {
        app(TalentShowControlService::class)->startShow($this->show);

        Livewire::actingAs($this->admin)
            ->test(AuditLogs::class, ['talentShow' => $this->show])
            ->assertSee('show_started')
            ->call('confirmClearHistory')
            ->assertSet('showClearConfirm', true)
            ->call('clearHistory')
            ->assertSet('showClearConfirm', false)
            ->assertSet('flashSuccess', 'Διαγράφηκαν 1 καταγραφές ιστορικού.')
            ->assertDontSee('show_started');
    }

    public function test_clear_history_when_empty_shows_message(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AuditLogs::class, ['talentShow' => $this->show])
            ->call('clearHistory')
            ->assertSet('flashSuccess', 'Δεν υπήρχαν καταγραφές προς διαγραφή.');
    }
}
