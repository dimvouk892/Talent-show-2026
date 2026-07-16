<?php

namespace App\Livewire\Admin\Judges;

use App\Models\Judge;
use App\Models\TalentShow;
use App\Services\JudgeAccessService;
use App\Services\QrCodeService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public int $talentShowId;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $title = '';

    public bool $is_active = true;

    public bool $is_final_voter = false;

    public bool $revokePreviousSessions = true;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public ?int $confirmRevokeQrId = null;

    public ?int $confirmRevokeSessionId = null;

    public ?int $confirmDeleteId = null;

    public bool $showRevokeAllConfirm = false;

    /** @var array<int, string> */
    public array $qrTokensByJudge = [];

    public function mount(TalentShow $talentShow): void
    {
        $this->authorize('view', $talentShow);
        $this->talentShowId = $talentShow->id;
        $this->loadQrTokensFromSession();
    }

    protected function loadQrTokensFromSession(): void
    {
        foreach ($this->getTalentShow()->judges()->get() as $judge) {
            $token = session('qr_token_'.$judge->id) ?? $judge->plainAccessToken();

            if ($token) {
                $this->qrTokensByJudge[$judge->id] = $token;
            }
        }
    }

    protected function resolvePlainToken(Judge $judge): ?string
    {
        return $this->qrTokensByJudge[$judge->id] ?? $judge->plainAccessToken();
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

    protected function notifyError(string $message): void
    {
        $this->flashError = $message;
        $this->flashSuccess = null;
    }

    public function cancelConfirm(): void
    {
        $this->confirmRevokeQrId = null;
        $this->confirmRevokeSessionId = null;
        $this->confirmDeleteId = null;
        $this->showRevokeAllConfirm = false;
    }

    public function askRevokeQr(int $id): void
    {
        $this->cancelConfirm();
        $this->confirmRevokeQrId = $id;
    }

    public function askRevokeSession(int $id): void
    {
        $this->cancelConfirm();
        $this->confirmRevokeSessionId = $id;
    }

    public function askDelete(int $id): void
    {
        $this->cancelConfirm();
        $this->confirmDeleteId = $id;
    }

    public function askRevokeAllSessions(): void
    {
        $this->cancelConfirm();
        $this->showRevokeAllConfirm = true;
    }

    public function confirmRevokeQr(JudgeAccessService $judgeAccessService): void
    {
        if (! $this->confirmRevokeQrId) {
            return;
        }

        $this->revokeQr($this->confirmRevokeQrId, $judgeAccessService);
        $this->confirmRevokeQrId = null;
    }

    public function confirmRevokeSession(JudgeAccessService $judgeAccessService): void
    {
        if (! $this->confirmRevokeSessionId) {
            return;
        }

        $this->revokeSession($this->confirmRevokeSessionId, $judgeAccessService);
        $this->confirmRevokeSessionId = null;
    }

    public function confirmDelete(): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }

        $this->delete($this->confirmDeleteId);
        $this->confirmDeleteId = null;
    }

    public function confirmRevokeAllSessions(JudgeAccessService $judgeAccessService): void
    {
        $this->revokeAllSessions($judgeAccessService);
        $this->showRevokeAllConfirm = false;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $judge = Judge::where('talent_show_id', $this->talentShowId)->findOrFail($id);
        $this->editingId = $judge->id;
        $this->name = $judge->name;
        $this->title = $judge->title ?? '';
        $this->is_active = $judge->is_active;
        $this->is_final_voter = $judge->is_final_voter;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'is_final_voter' => ['boolean'],
        ]);

        $talentShow = $this->getTalentShow();

        if ($this->editingId) {
            $judge = Judge::findOrFail($this->editingId);
            $wasActive = $judge->is_active;
            $judge->update([
                'name' => $this->name,
                'title' => $this->title ?: null,
                'is_active' => $this->is_active,
                'is_final_voter' => $this->is_final_voter,
            ]);

            if ($this->is_final_voter) {
                $talentShow->judges()
                    ->where('id', '!=', $judge->id)
                    ->where('is_final_voter', true)
                    ->update(['is_final_voter' => false]);
            }

            if ($wasActive && ! $this->is_active) {
                app(JudgeAccessService::class)->revokeAllSessions($judge);
            }

            $this->notifySuccess('Ο κριτής ενημερώθηκε.');
        } else {
            $judge = $talentShow->judges()->create([
                'name' => $this->name,
                'title' => $this->title ?: null,
                'display_order' => ($talentShow->judges()->max('display_order') ?? 0) + 1,
                'is_active' => $this->is_active,
                'is_final_voter' => $this->is_final_voter,
            ]);

            if ($this->is_final_voter) {
                $talentShow->judges()
                    ->where('id', '!=', $judge->id)
                    ->where('is_final_voter', true)
                    ->update(['is_final_voter' => false]);
            }

            $this->notifySuccess('Ο κριτής δημιουργήθηκε.');
        }

        $this->resetForm();
    }

    public function generateQr(int $id, JudgeAccessService $judgeAccessService): void
    {
        $judge = Judge::where('talent_show_id', $this->talentShowId)->findOrFail($id);
        $plainToken = $judgeAccessService->generateQrToken($judge, $this->revokePreviousSessions);

        $this->qrTokensByJudge[$judge->id] = $plainToken;
        session()->put('qr_token_'.$judge->id, $plainToken);

        $this->notifySuccess('Δημιουργήθηκε προσωπικό QR για '.$judge->name.'.');
    }

    public function generateAllQrs(JudgeAccessService $judgeAccessService): void
    {
        $this->authorize('update', $this->getTalentShow());

        $judges = $this->getTalentShow()->activeJudges()->ordered()->get();

        if ($judges->isEmpty()) {
            $this->notifyError('Δεν υπάρχουν ενεργοί κριτές.');

            return;
        }

        foreach ($judges as $judge) {
            $plainToken = $judgeAccessService->generateQrToken($judge, $this->revokePreviousSessions);
            $this->qrTokensByJudge[$judge->id] = $plainToken;
            session()->put('qr_token_'.$judge->id, $plainToken);
        }

        $this->notifySuccess('Δημιουργήθηκαν QR για '.$judges->count().' κριτές.');
    }

    public function revokeQr(int $id, JudgeAccessService $judgeAccessService): void
    {
        $this->authorize('update', $this->getTalentShow());
        $judge = Judge::where('talent_show_id', $this->talentShowId)->findOrFail($id);
        $judgeAccessService->revokeQrToken($judge);
        unset($this->qrTokensByJudge[$judge->id]);
        session()->forget('qr_token_'.$judge->id);
        $this->notifySuccess('Το QR ακυρώθηκε.');
    }

    public function revokeSession(int $id, JudgeAccessService $judgeAccessService): void
    {
        $this->authorize('update', $this->getTalentShow());
        $judge = Judge::where('talent_show_id', $this->talentShowId)->findOrFail($id);
        $judgeAccessService->revokeAllSessions($judge);
        $this->notifySuccess('Ο κριτής αποσυνδέθηκε.');
    }

    public function revokeAllSessions(JudgeAccessService $judgeAccessService): void
    {
        $this->authorize('update', $this->getTalentShow());
        $count = $judgeAccessService->revokeAllSessionsForTalentShow($this->getTalentShow());
        $this->notifySuccess("Ανακλήθηκαν {$count} συνεδρίες κριτών.");
    }

    public function delete(int $id): void
    {
        $judge = Judge::where('talent_show_id', $this->talentShowId)->findOrFail($id);

        if ($judge->hasVotes()) {
            $this->notifyError('Δεν μπορείτε να διαγράψετε κριτή με ψήφους.');

            return;
        }

        session()->forget('qr_token_'.$judge->id);
        unset($this->qrTokensByJudge[$judge->id]);
        $judge->delete();
        $this->notifySuccess('Ο κριτής διαγράφηκε.');
    }

    public function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->name = '';
        $this->title = '';
        $this->is_active = true;
        $this->is_final_voter = false;
    }

    public function render(QrCodeService $qrCodeService)
    {
        $talentShow = $this->getTalentShow();
        $judges = $talentShow->judges()
            ->withCount(['activeSessions as active_sessions_count'])
            ->ordered()
            ->get();

        $judgeStatus = $judges->mapWithKeys(function (Judge $judge) {
            return [$judge->id => [
                'has_active_session' => $judge->active_sessions_count > 0,
                'last_access' => $judge->last_access_at,
                'has_valid_qr' => $judge->hasValidToken(),
            ]];
        });

        $qrUrls = [];
        $qrPreviews = [];

        foreach ($judges as $judge) {
            $plainToken = $this->resolvePlainToken($judge);

            if ($plainToken) {
                $qrUrls[$judge->id] = $qrCodeService->accessUrl($judge, $plainToken);
                $qrPreviews[$judge->id] = $qrCodeService->generateSvg($judge, $plainToken, 140);
            }
        }

        return view('livewire.admin.judges.index', [
            'talentShow' => $talentShow,
            'judges' => $judges,
            'judgeStatus' => $judgeStatus,
            'qrUrls' => $qrUrls,
            'qrPreviews' => $qrPreviews,
        ]);
    }
}
