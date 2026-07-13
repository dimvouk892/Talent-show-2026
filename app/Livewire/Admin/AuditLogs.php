<?php

namespace App\Livewire\Admin;

use App\Models\TalentShow;
use App\Services\AuditLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class AuditLogs extends Component
{
    public int $talentShowId;

    public bool $showClearConfirm = false;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(TalentShow $talentShow): void
    {
        $this->authorize('view', $talentShow);
        $this->talentShowId = $talentShow->id;
    }

    protected function getTalentShow(): TalentShow
    {
        return TalentShow::findOrFail($this->talentShowId);
    }

    protected function notifySuccess(string $message): void
    {
        $this->flashSuccess = $message;
        $this->flashError = null;
    }

    public function confirmClearHistory(): void
    {
        $this->authorize('control', $this->getTalentShow());
        $this->showClearConfirm = true;
    }

    public function cancelClearHistory(): void
    {
        $this->showClearConfirm = false;
    }

    public function clearHistory(AuditLogService $auditLogService): void
    {
        $talentShow = $this->getTalentShow();
        $this->authorize('control', $talentShow);

        $deleted = $auditLogService->clearForTalentShow($talentShow);

        $this->showClearConfirm = false;
        $this->notifySuccess($deleted > 0
            ? "Διαγράφηκαν {$deleted} καταγραφές ιστορικού."
            : 'Δεν υπήρχαν καταγραφές προς διαγραφή.');
    }

    public function render(AuditLogService $auditLogService)
    {
        $talentShow = $this->getTalentShow();
        $logs = $auditLogService->queryForTalentShow($talentShow)
            ->latest()
            ->paginate(20);

        return view('livewire.admin.audit-logs', [
            'talentShow' => $talentShow,
            'logs' => $logs,
        ]);
    }
}
