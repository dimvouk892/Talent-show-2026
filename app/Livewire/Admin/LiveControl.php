<?php

namespace App\Livewire\Admin;

use App\Models\TalentShow;
use App\Models\Vote;
use App\Services\JudgeAccessService;
use App\Services\ResultsService;
use App\Services\ScoreCalculationService;
use App\Services\TalentShowControlService;
use App\Services\VoteService;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class LiveControl extends Component
{
    public int $talentShowId;

    public bool $showNextConfirm = false;

    public bool $showCorrectionForm = false;

    public ?int $correctingVoteId = null;

    public int $correctionScore = 5;

    public string $correctionReason = '';

    public ?int $selectedWinnerId = null;

    public bool $showWinnerSelect = false;

    public bool $showRestartConfirm = false;

    public bool $showArchiveConfirm = false;

    public bool $showRevokeAllConfirm = false;

    public bool $showClearScoresConfirm = false;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(TalentShow $talentShow): void
    {
        $this->authorize('control', $talentShow);
        $this->talentShowId = $talentShow->id;
    }

    protected function getTalentShow(): TalentShow
    {
        return TalentShow::with(['currentTeam', 'winnerTeam'])->findOrFail($this->talentShowId);
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

    public function pollLiveState(): void
    {
        // Trigger re-render for live scoreboard updates.
    }

    public function startShow(TalentShowControlService $control): void
    {
        try {
            $control->startShow($this->getTalentShow());
            $this->notifySuccess('Το Talent Show ξεκίνησε.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function openScoring(TalentShowControlService $control): void
    {
        try {
            $control->openScoring($this->getTalentShow());
            $this->notifySuccess('Η βαθμολόγηση άνοιξε.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function revealScores(TalentShowControlService $control): void
    {
        try {
            $control->revealScores($this->getTalentShow());
            $this->notifySuccess('Εμφανίστηκε το σκορ.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function hideScores(TalentShowControlService $control): void
    {
        $control->hideScores($this->getTalentShow());
        $this->notifySuccess('Αποκρύφθηκε το σκορ.');
    }

    public function dismissTeamIntro(TalentShowControlService $control): void
    {
        $control->dismissTeamIntro($this->getTalentShow());
        $this->notifySuccess('Ξεκίνησε η παρουσίαση της ομάδας.');
    }

    public function confirmNext(): void
    {
        $this->showNextConfirm = true;
    }

    public function nextTeam(TalentShowControlService $control): void
    {
        try {
            $control->nextTeam($this->getTalentShow());
            $this->showNextConfirm = false;
            $this->notifySuccess('Ενεργοποιήθηκε η επόμενη ομάδα.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function closeScoring(TalentShowControlService $control): void
    {
        $control->closeScoring($this->getTalentShow());
        $this->notifySuccess('Η βαθμολόγηση έκλεισε.');
    }

    public function showRanking(TalentShowControlService $control): void
    {
        $control->showRanking($this->getTalentShow());
        $this->notifySuccess('Εμφανίστηκε η κατάταξη.');
    }

    public function revealWinner(TalentShowControlService $control, ResultsService $results): void
    {
        try {
            $talentShow = $this->getTalentShow();
            $tied = $results->getTiedTeams($talentShow);

            if (! empty($tied) && ! $this->selectedWinnerId) {
                $this->showWinnerSelect = true;

                return;
            }

            $control->revealWinner($talentShow, $this->selectedWinnerId);
            $this->showWinnerSelect = false;
            $this->notifySuccess('Αποκαλύφθηκε ο νικητής.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function completeShow(TalentShowControlService $control): void
    {
        $control->completeShow($this->getTalentShow());
        $this->notifySuccess('Το Talent Show ολοκληρώθηκε.');
    }

    public function archiveShow(TalentShowControlService $control): void
    {
        $control->archiveShow($this->getTalentShow());
        session()->flash('success', 'Το Talent Show αρχειοθετήθηκε.');
        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    public function revokeAllJudgeSessions(JudgeAccessService $judgeAccessService): void
    {
        $count = $judgeAccessService->revokeAllSessionsForTalentShow($this->getTalentShow());
        $this->notifySuccess("Αποσυνδέθηκαν όλοι οι κριτές ({$count} sessions).");
    }

    public function confirmRestart(): void
    {
        $this->showRestartConfirm = true;
    }

    public function cancelDangerConfirm(): void
    {
        $this->showRestartConfirm = false;
        $this->showArchiveConfirm = false;
        $this->showRevokeAllConfirm = false;
        $this->showClearScoresConfirm = false;
    }

    public function askClearScores(): void
    {
        $this->cancelDangerConfirm();
        $this->showClearScoresConfirm = true;
    }

    public function confirmClearScores(TalentShowControlService $control): void
    {
        try {
            $control->clearScores($this->getTalentShow());
            $this->showClearScoresConfirm = false;
            $this->showNextConfirm = false;
            $this->showWinnerSelect = false;
            $this->notifySuccess('Οι βαθμολογίες διαγράφηκαν. Η εκδήλωση επανήλθε σε κατάσταση «Έτοιμο».');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
            $this->showClearScoresConfirm = false;
        }
    }

    public function askArchive(): void
    {
        $this->cancelDangerConfirm();
        $this->showArchiveConfirm = true;
    }

    public function askRevokeAllSessions(): void
    {
        $this->cancelDangerConfirm();
        $this->showRevokeAllConfirm = true;
    }

    public function confirmRevokeAllJudgeSessions(JudgeAccessService $judgeAccessService): void
    {
        $this->revokeAllJudgeSessions($judgeAccessService);
        $this->showRevokeAllConfirm = false;
    }

    public function restartShow(TalentShowControlService $control): void
    {
        try {
            $control->restartShow($this->getTalentShow());
            $this->showRestartConfirm = false;
            $this->showNextConfirm = false;
            $this->showWinnerSelect = false;
            $this->notifySuccess('Η εκδήλωση επανεκκινήθηκε και άνοιξε η βαθμολόγηση από την 1η ομάδα.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
            $this->showRestartConfirm = false;
        }
    }

    public function openCorrection(int $voteId): void
    {
        $vote = Vote::findOrFail($voteId);
        $this->correctingVoteId = $vote->id;
        $this->correctionScore = $vote->score;
        $this->correctionReason = '';
        $this->showCorrectionForm = true;
    }

    public function correctVote(VoteService $voteService): void
    {
        $this->validate([
            'correctionScore' => ['required', 'integer', 'min:1', 'max:10'],
            'correctionReason' => ['required', 'string', 'min:5'],
        ]);

        try {
            $vote = Vote::findOrFail($this->correctingVoteId);
            $voteService->correct($vote, $this->correctionScore, $this->correctionReason, auth()->user());
            $this->showCorrectionForm = false;
            $this->correctingVoteId = null;
            $this->notifySuccess('Η βαθμολογία διορθώθηκε.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function render(
        ScoreCalculationService $scoreCalculationService,
        TalentShowControlService $control,
        ResultsService $resultsService,
    ) {
        $talentShow = $this->getTalentShow();

        $currentTeam = $talentShow->currentTeam;
        $scores = $currentTeam ? $scoreCalculationService->forTeam($currentTeam, $talentShow) : null;
        $judgeStatus = $currentTeam
            ? $scoreCalculationService->judgeVoteStatus($talentShow, $currentTeam)
            : [];
        $canProceed = $control->canProceedToNext($talentShow);
        $tiedTeams = $resultsService->getTiedTeams($talentShow);

        return view('livewire.admin.live-control', [
            'talentShow' => $talentShow,
            'currentTeam' => $currentTeam,
            'scores' => $scores,
            'judgeStatus' => $judgeStatus,
            'canProceed' => $canProceed,
            'tiedTeams' => $tiedTeams,
            'flowHint' => $control->flowHint($talentShow),
            'canStartShow' => $control->canStartShow($talentShow),
            'canOpenScoring' => $control->canOpenScoring($talentShow),
            'canRevealScores' => $control->canRevealScores($talentShow),
            'canHideScores' => $control->canHideScores($talentShow),
            'canCloseScoring' => $control->canCloseScoring($talentShow),
            'canShowRanking' => $control->canShowRanking($talentShow),
            'canRevealWinner' => $control->canRevealWinner($talentShow),
            'canCompleteShow' => $control->canCompleteShow($talentShow),
        ]);
    }
}
