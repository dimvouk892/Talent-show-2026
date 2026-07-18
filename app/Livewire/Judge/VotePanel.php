<?php

namespace App\Livewire\Judge;

use App\Enums\TalentShowStatus;
use App\Models\Judge;
use App\Models\TalentShow;
use App\Models\Team;
use App\Services\JudgeAccessService;
use App\Services\ScoreCalculationService;
use App\Services\VoteService;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.judge')]
class VotePanel extends Component
{
    public Judge $judge;

    public ?int $selectedScore = null;

    public ?int $selectedTeamId = null;

    public bool $showConfirm = false;

    public bool $hasVoted = false;

    public bool $hasFinalVoted = false;

    public ?int $lastKnownTeamId = null;

    public bool $teamJustChanged = false;

    public bool $showCompleted = false;

    public function mount(Judge $judge): void
    {
        $this->judge = $judge;
    }

    protected function getJudge(): ?Judge
    {
        return $this->judge->fresh(['talentShow']);
    }

    protected function getTalentShow(): ?TalentShow
    {
        return TalentShow::with('currentTeam')->find($this->judge->talent_show_id);
    }

    public function keepAlive(JudgeAccessService $judgeAccessService): void
    {
        $judge = $judgeAccessService->keepAlive(request(), $this->judge);

        if (! $judge) {
            $this->redirect(route('judge.access.denied'), navigate: true);

            return;
        }

        $talentShow = $judge->talentShow;

        if ($talentShow->status->isFinished()) {
            $this->showCompleted = true;
            $judgeAccessService->logout(request(), $judge);

            return;
        }

        $this->showCompleted = false;
    }

    public function selectScore(int $score): void
    {
        $this->selectedScore = $score;
        $this->showConfirm = false;
    }

    public function selectTeam(int $teamId): void
    {
        $this->selectedTeamId = $teamId;
        $this->showConfirm = false;
    }

    public function confirmSubmit(): void
    {
        $judge = $this->getJudge();

        if ($judge?->is_final_voter) {
            if (! $this->selectedTeamId) {
                return;
            }

            $this->selectedScore = app(VoteService::class)->finalVoteScore();
            $this->showConfirm = true;

            return;
        }

        if (! $this->selectedScore) {
            return;
        }

        $this->showConfirm = true;
    }

    public function submit(VoteService $voteService): void
    {
        if (! $this->selectedScore) {
            return;
        }

        $judge = $this->getJudge();
        $talentShow = $this->getTalentShow();
        $currentTeam = $talentShow?->currentTeam;

        if (! $judge || ! $currentTeam) {
            session()->flash('error', 'Δεν υπάρχει ενεργή ομάδα.');

            return;
        }

        if ($judge->is_final_voter) {
            session()->flash('error', 'Ο κριτής τελικής ψήφου ψηφίζει μόνο στο τέλος.');

            return;
        }

        if ($talentShow->status !== TalentShowStatus::ScoringOpen) {
            session()->flash('error', 'Η βαθμολόγηση δεν είναι ανοιχτή.');

            return;
        }

        try {
            $voteService->submit($judge, $currentTeam, $this->selectedScore);
            $this->hasVoted = true;
            $this->showConfirm = false;
        } catch (InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function submitFinalVote(VoteService $voteService): void
    {
        if (! $this->selectedTeamId) {
            return;
        }

        $this->selectedScore = $voteService->finalVoteScore();
        $judge = $this->getJudge();
        $team = Team::find($this->selectedTeamId);

        if (! $judge || ! $team) {
            session()->flash('error', 'Επιλέξτε ομάδα.');

            return;
        }

        try {
            $voteService->submitFinalVote($judge, $team, $this->selectedScore);
            $this->hasFinalVoted = true;
            $this->showConfirm = false;
        } catch (InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(ScoreCalculationService $scoreCalculationService, JudgeAccessService $judgeAccessService)
    {
        // Always scope auth to this component's judge — Livewire polls have no route {judge}.
        $judge = $judgeAccessService->keepAlive(request(), $this->judge);
        $voteService = app(VoteService::class);
        $allowedScores = $voteService->allowedScores();
        $finalScore = $voteService->finalVoteScore();

        if (! $judge) {
            return $this->judgePanelView([
                'judge' => null,
                'talentShow' => null,
                'currentTeam' => null,
                'voteProgress' => ['voted' => 0, 'total' => 0],
                'sessionInvalid' => true,
                'judgeScene' => 'reconnecting',
                'allowedScores' => $allowedScores,
                'finalScore' => $finalScore,
                'finalTeams' => collect(),
                'isFinalVoter' => false,
                'finalVoteOpen' => false,
            ]);
        }

        $talentShow = $judge->talentShow()->with('currentTeam')->first();

        if ($talentShow && $talentShow->status->isFinished()) {
            $this->showCompleted = true;

            return $this->judgePanelView([
                'judge' => $judge,
                'talentShow' => $talentShow,
                'currentTeam' => null,
                'voteProgress' => ['voted' => 0, 'total' => 0],
                'sessionInvalid' => false,
                'judgeScene' => 'completed',
                'allowedScores' => $allowedScores,
                'finalScore' => $finalScore,
                'finalTeams' => collect(),
                'isFinalVoter' => $judge->is_final_voter,
                'finalVoteOpen' => false,
            ]);
        }

        $this->showCompleted = false;
        $isFinalVoter = (bool) $judge->is_final_voter;
        $finalVoteOpen = (bool) ($talentShow?->final_vote_open);
        $finalTeams = $isFinalVoter && $talentShow
            ? $talentShow->activeTeams()->ordered()->get()
            : collect();

        if ($isFinalVoter) {
            $this->selectedScore = $finalScore;
        }

        if ($isFinalVoter && $judge->votes()->where('talent_show_id', $talentShow->id)->exists()) {
            $this->hasFinalVoted = true;
        }

        if ($isFinalVoter) {
            return $this->judgePanelView([
                'judge' => $judge,
                'talentShow' => $talentShow,
                'currentTeam' => null,
                'voteProgress' => ['voted' => 0, 'total' => 0],
                'sessionInvalid' => false,
                'judgeScene' => $this->finalJudgeSceneKey($finalVoteOpen),
                'allowedScores' => $allowedScores,
                'finalScore' => $finalScore,
                'finalTeams' => $finalTeams,
                'isFinalVoter' => true,
                'finalVoteOpen' => $finalVoteOpen,
            ]);
        }

        $currentTeam = $talentShow?->currentTeam;

        $voteProgress = $talentShow && $currentTeam
            ? $scoreCalculationService->votesProgress($talentShow, $currentTeam)
            : ['voted' => 0, 'total' => 0];

        if ($currentTeam) {
            if ($this->lastKnownTeamId !== null && $this->lastKnownTeamId !== $currentTeam->id) {
                $this->teamJustChanged = true;
                $this->selectedScore = null;
                $this->showConfirm = false;
                $this->hasVoted = false;
            }
            $this->lastKnownTeamId = $currentTeam->id;
        } else {
            $this->lastKnownTeamId = null;
            $this->teamJustChanged = false;
            $this->hasVoted = false;
            $this->selectedScore = null;
            $this->showConfirm = false;
        }

        if ($judge && $currentTeam && $currentTeam->votes()->where('judge_id', $judge->id)->exists()) {
            $this->hasVoted = true;
            $this->teamJustChanged = false;
        } elseif ($currentTeam) {
            $this->hasVoted = false;
        }

        return $this->judgePanelView([
            'judge' => $judge,
            'talentShow' => $talentShow,
            'currentTeam' => $currentTeam,
            'voteProgress' => $voteProgress,
            'sessionInvalid' => false,
            'judgeScene' => $this->judgeSceneKey($currentTeam),
            'allowedScores' => $allowedScores,
            'finalScore' => $finalScore,
            'finalTeams' => collect(),
            'isFinalVoter' => false,
            'finalVoteOpen' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function judgePanelView(array $data)
    {
        $layoutJudge = $data['judge'] ?? $this->judge;

        return view('livewire.judge.vote-panel', $data)->layoutData([
            'layoutJudge' => $layoutJudge,
            'layoutTalentShow' => $data['talentShow'] ?? $layoutJudge?->talentShow,
        ]);
    }

    protected function finalJudgeSceneKey(bool $finalVoteOpen): string
    {
        if ($this->showCompleted) {
            return 'completed';
        }

        if ($this->hasFinalVoted) {
            return 'final-done';
        }

        if ($finalVoteOpen && $this->showConfirm) {
            return 'final-confirm';
        }

        if ($finalVoteOpen) {
            return 'final-voting';
        }

        return 'final-waiting';
    }

    protected function judgeSceneKey($currentTeam): string
    {
        if ($this->showCompleted) {
            return 'completed';
        }

        if (! $currentTeam) {
            return 'waiting';
        }

        if ($this->hasVoted) {
            return 'team-'.$currentTeam->id.'-voted';
        }

        if ($this->showConfirm) {
            return 'team-'.$currentTeam->id.'-confirm';
        }

        return 'team-'.$currentTeam->id.'-scoring';
    }
}
