<?php

namespace App\Livewire\Admin;

use App\Models\Judge;
use App\Models\TalentShow;
use App\Models\Team;
use App\Models\Vote;
use App\Services\ResultsService;
use App\Services\TalentShowControlService;
use App\Services\VoteService;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Results extends Component
{
    public int $talentShowId;

    public ?int $selectedWinnerId = null;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public bool $showFinalVoteForm = false;

    public ?int $finalVoteId = null;

    public ?int $finalVoteTeamId = null;

    public int $finalVoteScore = 12;

    public string $finalVoteReason = '';

    public bool $showCellForm = false;

    public ?int $cellTeamId = null;

    public ?int $cellJudgeId = null;

    public string $cellJudgeName = '';

    public string $cellTeamName = '';

    public int $cellScore = 9;

    public string $cellReason = '';

    public bool $cellHadVote = false;

    public function mount(TalentShow $talentShow): void
    {
        $this->authorize('view', $talentShow);
        $this->talentShowId = $talentShow->id;
    }

    protected function getTalentShow(): TalentShow
    {
        return TalentShow::findOrFail($this->talentShowId);
    }

    public function revealWinner(TalentShowControlService $control): void
    {
        try {
            $control->revealWinner($this->getTalentShow(), $this->selectedWinnerId);
            $this->flashSuccess = 'Αποκαλύφθηκε ο νικητής.';
            $this->flashError = null;
        } catch (InvalidArgumentException $e) {
            $this->flashError = $e->getMessage();
            $this->flashSuccess = null;
        }
    }

    public function pollResults(): void
    {
        // Re-render for live scoreboard updates as votes arrive.
    }

    public function openCellEdit(int $teamId, int $judgeId): void
    {
        $this->authorize('update', $this->getTalentShow());

        $talentShow = $this->getTalentShow();
        $team = Team::where('talent_show_id', $talentShow->id)->findOrFail($teamId);
        $judge = Judge::where('talent_show_id', $talentShow->id)->findOrFail($judgeId);

        $existing = $judge->is_final_voter
            ? $judge->votes()->where('talent_show_id', $talentShow->id)->first()
            : Vote::where('team_id', $team->id)->where('judge_id', $judge->id)->first();

        $this->cellTeamId = $team->id;
        $this->cellJudgeId = $judge->id;
        $this->cellTeamName = $team->name;
        $this->cellJudgeName = $judge->name.($judge->is_final_voter ? ' (τελική)' : '');
        $this->cellHadVote = $existing !== null;
        $this->cellScore = $existing?->score ?? 9;
        $this->cellReason = $existing
            ? 'Διόρθωση βαθμού από διαχειριστή'
            : 'Καταχώρηση βαθμού από διαχειριστή';
        $this->showCellForm = true;
        $this->showFinalVoteForm = false;
    }

    public function closeCellForm(): void
    {
        $this->showCellForm = false;
        $this->cellTeamId = null;
        $this->cellJudgeId = null;
        $this->cellJudgeName = '';
        $this->cellTeamName = '';
        $this->cellReason = '';
        $this->cellScore = 9;
        $this->cellHadVote = false;
    }

    public function saveCellScore(VoteService $voteService): void
    {
        $this->authorize('update', $this->getTalentShow());

        $this->validate([
            'cellTeamId' => ['required', 'integer'],
            'cellJudgeId' => ['required', 'integer'],
            'cellScore' => ['required', 'integer', 'in:9,10,12'],
            'cellReason' => ['required', 'string', 'min:5'],
        ]);

        try {
            $talentShow = $this->getTalentShow();
            $team = Team::where('talent_show_id', $talentShow->id)->findOrFail($this->cellTeamId);
            $judge = Judge::where('talent_show_id', $talentShow->id)->findOrFail($this->cellJudgeId);

            $voteService->adminUpsertScore(
                $judge,
                $team,
                $this->cellScore,
                $this->cellReason,
                auth()->user(),
            );

            $this->flashSuccess = $this->cellHadVote
                ? 'Η βαθμολογία διορθώθηκε.'
                : 'Καταχωρίστηκε βαθμός για '.$judge->name.'.';
            $this->flashError = null;
            $this->closeCellForm();
        } catch (InvalidArgumentException $e) {
            $this->flashError = $e->getMessage();
            $this->flashSuccess = null;
        }
    }

    public function openFinalVoteForm(): void
    {
        $this->authorize('update', $this->getTalentShow());

        $talentShow = $this->getTalentShow();
        $finalVoter = $talentShow->finalVoter();

        if (! $finalVoter) {
            $this->flashError = 'Δεν υπάρχει κριτής τελικής ψήφου.';
            $this->flashSuccess = null;

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
        $this->showCellForm = false;
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
        $this->authorize('update', $this->getTalentShow());

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
                $this->flashSuccess = 'Η τελική ψήφος διορθώθηκε.';
            } else {
                $voteService->submitFinalVoteOnBehalf(
                    $finalVoter,
                    $team,
                    $this->finalVoteScore,
                    $this->finalVoteReason,
                    auth()->user(),
                );
                $this->flashSuccess = 'Καταχωρίστηκε η τελική ψήφος.';
            }

            $this->flashError = null;
            $this->closeFinalVoteForm();
        } catch (InvalidArgumentException $e) {
            $this->flashError = $e->getMessage();
            $this->flashSuccess = null;
        }
    }

    public function render(ResultsService $resultsService)
    {
        $talentShow = $this->getTalentShow();
        $report = $resultsService->getDetailedReport($talentShow);
        $tiedTeams = $resultsService->getTiedTeams($talentShow);
        $winner = $resultsService->getWinner($talentShow);
        $finalVoter = $talentShow->finalVoter();
        $finalVote = $finalVoter
            ? $finalVoter->votes()->where('talent_show_id', $talentShow->id)->with('team')->first()
            : null;

        return view('livewire.admin.results', [
            'talentShow' => $talentShow,
            'summary' => $report['summary'],
            'judges' => $report['judges'],
            'ranking' => $report['ranking'],
            'tiedTeams' => $tiedTeams,
            'winner' => $winner,
            'finalVoter' => $finalVoter,
            'finalVote' => $finalVote,
            'teams' => $talentShow->activeTeams()->ordered()->get(),
        ]);
    }
}
