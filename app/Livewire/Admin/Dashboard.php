<?php

namespace App\Livewire\Admin;

use App\Models\TalentShow;
use App\Services\JudgeAccessService;
use App\Services\ResultsService;
use App\Services\ScoreCalculationService;
use App\Services\TalentShowManagementService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public ?int $confirmDeleteId = null;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

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

    public function askDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function confirmDelete(TalentShowManagementService $managementService): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }

        $show = TalentShow::findOrFail($this->confirmDeleteId);
        $this->authorize('delete', $show);

        $managementService->delete($show);

        $this->confirmDeleteId = null;
        $this->notifySuccess('Το Talent Show «'.$show->title.'» διαγράφηκε.');
    }

    public function render(
        JudgeAccessService $judgeAccessService,
        ScoreCalculationService $scoreCalculationService,
        ResultsService $resultsService,
    ) {
        $shows = TalentShow::notArchived()->latest()->get();

        $showData = $shows->map(function (TalentShow $show) use ($judgeAccessService, $scoreCalculationService, $resultsService) {
            $progress = $resultsService->getProgress($show);
            $voteProgress = $scoreCalculationService->votesProgress($show);

            return [
                'show' => $show,
                'teams_count' => $show->activeTeams()->count(),
                'judges_count' => $show->activeJudges()->count(),
                'connected_judges' => $judgeAccessService->getConnectedJudgesCount($show),
                'current_team' => $show->currentTeam,
                'votes_progress' => $voteProgress,
                'overall_progress' => $progress,
                'has_votes' => $show->votes()->exists(),
            ];
        });

        $confirmDeleteShow = $this->confirmDeleteId
            ? TalentShow::find($this->confirmDeleteId)
            : null;

        return view('livewire.admin.dashboard', [
            'showData' => $showData,
            'confirmDeleteShow' => $confirmDeleteShow,
        ]);
    }
}
