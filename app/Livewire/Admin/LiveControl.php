<?php

namespace App\Livewire\Admin;

use App\Models\Judge;
use App\Models\TalentShow;
use App\Models\Team;
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

    public ?int $proxyJudgeId = null;

    public bool $showFinalVoteForm = false;

    public ?int $finalVoteId = null;

    public ?int $finalVoteTeamId = null;

    public int $finalVoteScore = 12;

    public string $finalVoteReason = '';

    public int $correctionScore = 9;

    public string $correctionReason = '';

    public ?int $selectedWinnerId = null;

    public bool $showWinnerSelect = false;

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
            $talentShow = $this->getTalentShow();

            // Αν η εκδήλωση δεν είναι σε draft/ready, καθαρίζουμε και ξεκινάμε από την αρχή.
            if (! in_array($talentShow->status->value, ['draft', 'ready'], true) || $talentShow->votes()->exists()) {
                $talentShow = $control->clearScores($talentShow);
            }

            $control->openScoring($talentShow);
            $this->notifySuccess('Η ψηφοφορία ξεκίνησε.');
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
        $talentShow = $this->getTalentShow();
        $this->notifySuccess(
            $talentShow->final_vote_open
                ? 'Η βαθμολόγηση έκλεισε. Αναμονή τελικής ψήφου.'
                : 'Η βαθμολόγηση έκλεισε.'
        );
    }

    public function openFinalVote(TalentShowControlService $control): void
    {
        try {
            $control->openFinalVote($this->getTalentShow());
            $this->notifySuccess('Η τελική ψήφος άνοιξε για τον ειδικό κριτή.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function showRanking(TalentShowControlService $control): void
    {
        try {
            $control->showRanking($this->getTalentShow());
            $this->notifySuccess('Εμφανίστηκε η κατάταξη.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
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

    public function startPodiumReveal(TalentShowControlService $control, ResultsService $results): void
    {
        try {
            $talentShow = $this->getTalentShow();
            $tied = $results->getTiedTeams($talentShow);

            if (! empty($tied) && ! $this->selectedWinnerId && ! $talentShow->winner_team_id) {
                $this->showWinnerSelect = true;

                return;
            }

            $control->startPodiumReveal($talentShow, $this->selectedWinnerId);
            $this->showWinnerSelect = false;
            $this->notifySuccess('Ξεκίνησε η αποκάλυψη top 5.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function nextPodiumReveal(TalentShowControlService $control): void
    {
        try {
            $control->nextPodiumReveal($this->getTalentShow());
            $talentShow = $this->getTalentShow();
            $this->notifySuccess(
                $talentShow->winner_revealed
                    ? 'Αποκαλύφθηκε η 1η θέση.'
                    : 'Επόμενη θέση αποκαλύφθηκε.'
            );
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function previousPodiumReveal(TalentShowControlService $control): void
    {
        try {
            $control->previousPodiumReveal($this->getTalentShow());
            $this->notifySuccess('Επιστροφή στο προηγούμενο βήμα.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function showFinalOverview(TalentShowControlService $control): void
    {
        try {
            $control->showFinalOverview($this->getTalentShow());
            $this->notifySuccess('Εμφανίστηκαν όλες οι ομάδες και το γράφημα.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function hideFinalOverview(TalentShowControlService $control): void
    {
        $control->hideFinalOverview($this->getTalentShow());
        $this->notifySuccess('Αποκρύφθηκε η πλήρης κατάταξη.');
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

    public function openCorrection(int $voteId): void
    {
        $vote = Vote::findOrFail($voteId);
        $this->correctingVoteId = $vote->id;
        $this->proxyJudgeId = null;
        $this->correctionScore = $vote->score;
        $this->correctionReason = '';
        $this->showCorrectionForm = true;
    }

    public function openProxyVote(int $judgeId): void
    {
        $judge = Judge::where('talent_show_id', $this->talentShowId)->findOrFail($judgeId);
        $this->proxyJudgeId = $judge->id;
        $this->correctingVoteId = null;
        $this->correctionScore = 9;
        $this->correctionReason = 'Καταχώρηση από διαχειριστή λόγω προβλήματος συσκευής';
        $this->showCorrectionForm = true;
    }

    public function closeScoreForm(): void
    {
        $this->showCorrectionForm = false;
        $this->correctingVoteId = null;
        $this->proxyJudgeId = null;
        $this->correctionReason = '';
    }

    public function openFinalVoteForm(): void
    {
        $talentShow = $this->getTalentShow();
        $finalVoter = $talentShow->finalVoter();

        if (! $finalVoter) {
            $this->notifyError('Δεν υπάρχει κριτής τελικής ψήφου.');

            return;
        }

        $existing = $finalVoter->votes()->where('talent_show_id', $talentShow->id)->first();

        $this->finalVoteId = $existing?->id;
        $this->finalVoteTeamId = $existing?->team_id;
        $this->finalVoteScore = $existing?->score ?? 12;
        $this->finalVoteReason = $existing
            ? 'Διόρθωση τελικής ψήφου από διαχειριστή'
            : 'Καταχώρηση τελικής ψήφου από διαχειριστή';
        $this->showFinalVoteForm = true;
    }

    public function closeFinalVoteForm(): void
    {
        $this->showFinalVoteForm = false;
        $this->finalVoteId = null;
        $this->finalVoteTeamId = null;
        $this->finalVoteReason = '';
        $this->finalVoteScore = 12;
    }

    public function saveFinalVote(VoteService $voteService): void
    {
        $this->validate([
            'finalVoteTeamId' => ['required', 'integer'],
            'finalVoteScore' => ['required', 'integer', 'in:9,10,12'],
            'finalVoteReason' => ['required', 'string', 'min:5'],
        ]);

        try {
            $talentShow = $this->getTalentShow();
            $finalVoter = $talentShow->finalVoter();
            $team = Team::where('talent_show_id', $talentShow->id)->findOrFail($this->finalVoteTeamId);

            if (! $finalVoter) {
                throw new InvalidArgumentException('Δεν υπάρχει κριτής τελικής ψήφου.');
            }

            if ($this->finalVoteId) {
                $vote = Vote::findOrFail($this->finalVoteId);
                $voteService->correctFinalVote(
                    $vote,
                    $team,
                    $this->finalVoteScore,
                    $this->finalVoteReason,
                    auth()->user(),
                );
                $this->notifySuccess('Η τελική ψήφος διορθώθηκε.');
            } else {
                $voteService->submitFinalVoteOnBehalf(
                    $finalVoter,
                    $team,
                    $this->finalVoteScore,
                    $this->finalVoteReason,
                    auth()->user(),
                );
                $this->notifySuccess('Καταχωρίστηκε η τελική ψήφος.');
            }

            $this->closeFinalVoteForm();
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function correctVote(VoteService $voteService): void
    {
        $this->validate([
            'correctionScore' => ['required', 'integer', 'in:9,10,12'],
            'correctionReason' => ['required', 'string', 'min:5'],
        ]);

        try {
            if ($this->proxyJudgeId) {
                $talentShow = $this->getTalentShow();
                $team = $talentShow->currentTeam;

                if (! $team) {
                    throw new InvalidArgumentException('Δεν υπάρχει ενεργή ομάδα.');
                }

                $judge = Judge::findOrFail($this->proxyJudgeId);
                $voteService->submitOnBehalf(
                    $judge,
                    $team,
                    $this->correctionScore,
                    $this->correctionReason,
                    auth()->user(),
                );
                $this->notifySuccess('Καταχωρίστηκε βαθμός για '.$judge->name.'.');
            } else {
                $vote = Vote::findOrFail($this->correctingVoteId);
                $voteService->correct($vote, $this->correctionScore, $this->correctionReason, auth()->user());
                $this->notifySuccess('Η βαθμολογία διορθώθηκε.');
            }

            $this->closeScoreForm();
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
        $podium = $resultsService->getPodiumRevealState($talentShow);
        $finalVoter = $talentShow->finalVoter();
        $finalVote = $finalVoter
            ? $finalVoter->votes()->where('talent_show_id', $talentShow->id)->with('team')->first()
            : null;
        $panelReport = $resultsService->getDetailedReport($talentShow);

        return view('livewire.admin.live-control', [
            'talentShow' => $talentShow,
            'currentTeam' => $currentTeam,
            'scores' => $scores,
            'judgeStatus' => $judgeStatus,
            'canProceed' => $canProceed,
            'tiedTeams' => $tiedTeams,
            'podium' => $podium,
            'flowHint' => $control->flowHint($talentShow),
            'canStartShow' => $control->canStartShow($talentShow),
            'canOpenScoring' => $control->canOpenScoring($talentShow),
            'canRevealScores' => $control->canRevealScores($talentShow),
            'canHideScores' => $control->canHideScores($talentShow),
            'canCloseScoring' => $control->canCloseScoring($talentShow),
            'canOpenFinalVote' => $control->canOpenFinalVote($talentShow),
            'canShowRanking' => $control->canShowRanking($talentShow),
            'hasPendingFinalVote' => $talentShow->hasPendingFinalVote(),
            'finalVoter' => $finalVoter,
            'finalVote' => $finalVote,
            'teams' => $talentShow->activeTeams()->ordered()->get(),
            'canRevealWinner' => $control->canRevealWinner($talentShow),
            'canStartPodiumReveal' => $control->canStartPodiumReveal($talentShow),
            'canAdvancePodium' => $control->canAdvancePodium($talentShow),
            'canRewindPodium' => $control->canRewindPodium($talentShow),
            'canShowFinalOverview' => $control->canShowFinalOverview($talentShow),
            'canHideFinalOverview' => $control->canHideFinalOverview($talentShow),
            'canCompleteShow' => $control->canCompleteShow($talentShow),
            'panelJudges' => $panelReport['judges'],
            'panelRanking' => $panelReport['ranking'],
        ]);
    }
}
