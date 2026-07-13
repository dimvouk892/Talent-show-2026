<?php

namespace App\Livewire\Judge;

use App\Enums\TalentShowStatus;
use App\Models\Judge;
use App\Models\TalentShow;
use App\Services\JudgeAccessService;
use App\Services\ScoreCalculationService;
use App\Services\VoteService;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.judge')]
class VotePanel extends Component
{
    public ?int $selectedScore = null;

    public bool $showConfirm = false;

    public bool $hasVoted = false;

    public ?int $lastKnownTeamId = null;

    public bool $teamJustChanged = false;

    public bool $showCompleted = false;

    public function mount(Judge $judge): void
    {
        if ((int) session('judge_id') !== $judge->id) {
            $this->redirect(route('judge.access.denied'), navigate: true);
        }
    }

    protected function getJudge(): ?Judge
    {
        return Judge::find(session('judge_id'));
    }

    protected function getTalentShow(): ?TalentShow
    {
        return TalentShow::with('currentTeam')->find(session('talent_show_id'));
    }

    public function keepAlive(JudgeAccessService $judgeAccessService): void
    {
        $judge = $judgeAccessService->keepAlive(request());

        if (! $judge) {
            $this->redirect(route('judge.access.denied'), navigate: true);

            return;
        }

        $talentShow = $judge->talentShow;

        if ($talentShow->status->isFinished()) {
            $this->showCompleted = true;
            $judgeAccessService->logout(request());

            return;
        }

        $this->showCompleted = false;
    }

    public function selectScore(int $score): void
    {
        $this->selectedScore = $score;
        $this->showConfirm = false;
    }

    public function confirmSubmit(): void
    {
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

    public function render(ScoreCalculationService $scoreCalculationService, JudgeAccessService $judgeAccessService)
    {
        $judge = $judgeAccessService->keepAlive(request());

        if (! $judge) {
            return view('livewire.judge.vote-panel', [
                'judge' => null,
                'talentShow' => null,
                'currentTeam' => null,
                'voteProgress' => ['voted' => 0, 'total' => 0],
                'sessionInvalid' => true,
                'judgeScene' => 'reconnecting',
            ]);
        }

        $talentShow = $judge->talentShow()->with('currentTeam')->first();

        if ($talentShow && $talentShow->status->isFinished()) {
            $this->showCompleted = true;

            return view('livewire.judge.vote-panel', [
                'judge' => $judge,
                'talentShow' => $talentShow,
                'currentTeam' => null,
                'voteProgress' => ['voted' => 0, 'total' => 0],
                'sessionInvalid' => false,
                'judgeScene' => 'completed',
            ]);
        }

        $this->showCompleted = false;
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

        return view('livewire.judge.vote-panel', [
            'judge' => $judge,
            'talentShow' => $talentShow,
            'currentTeam' => $currentTeam,
            'voteProgress' => $voteProgress,
            'sessionInvalid' => false,
            'judgeScene' => $this->judgeSceneKey($currentTeam),
        ]);
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
