<?php

namespace App\Livewire\Presentation;

use App\Models\TalentShow;
use App\Services\ResultsService;
use App\Services\ScoreCalculationService;
use App\Services\TalentShowControlService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.presentation')]
class ShowScreen extends Component
{
    public TalentShow $talentShow;

    public function mount(TalentShow $talentShow): void
    {
        $this->talentShow = $talentShow;
    }

    public function finishIntro(TalentShowControlService $control): void
    {
        $control->dismissTeamIntro($this->talentShow);
        $this->talentShow->refresh();
    }

    public function finishOpeningVideo(TalentShowControlService $control): void
    {
        $control->dismissOpeningVideo($this->talentShow);
        $this->talentShow->refresh();
    }

    public function finishClosingVideo(TalentShowControlService $control): void
    {
        $control->dismissClosingVideo($this->talentShow);
        $this->talentShow->refresh();
    }

    public function render(
        ScoreCalculationService $scoreCalculationService,
        ResultsService $resultsService,
    ) {
        $this->talentShow->refresh()->load(['currentTeam', 'winnerTeam']);

        $currentTeam = $this->talentShow->currentTeam;
        $scores = $currentTeam ? $scoreCalculationService->forTeam($currentTeam, $this->talentShow) : null;
        $voteProgress = $scoreCalculationService->votesProgress($this->talentShow);
        $judgeStatus = $currentTeam && $this->talentShow->show_live_scores && $scores && $scores['votes_count'] > 0
            ? $scoreCalculationService->judgeVoteStatus($this->talentShow, $currentTeam)
            : [];
        $ranking = $this->talentShow->show_ranking
            ? array_values(array_filter(
                $resultsService->getRanking($this->talentShow),
                fn ($item) => $item['is_complete']
            ))
            : [];
        $winner = $this->talentShow->winner_revealed
            ? $resultsService->getWinner($this->talentShow)
            : null;

        return view('livewire.presentation.show-screen', [
            'currentTeam' => $currentTeam,
            'scores' => $scores,
            'voteProgress' => $voteProgress,
            'judgeStatus' => $judgeStatus,
            'ranking' => $ranking,
            'winner' => $winner,
            'presentationScene' => $this->presentationSceneKey($currentTeam, $ranking, $winner),
        ]);
    }

    protected function presentationSceneKey($currentTeam, array $ranking, $winner): string
    {
        $show = $this->talentShow;

        if ($show->showing_closing_video && $show->closing_video_path) {
            return 'closing';
        }

        if ($show->showing_opening_video && $show->opening_video_path) {
            return 'opening';
        }

        if ($winner && $show->winner_revealed) {
            return 'winner';
        }

        if ($show->show_ranking && count($ranking) > 0) {
            return 'ranking';
        }

        if ($currentTeam && $show->showing_team_intro && $currentTeam->video_path) {
            return 'intro-'.$currentTeam->id;
        }

        if ($currentTeam) {
            return 'team-'.$currentTeam->id;
        }

        if ($show->shouldDisplayWaitingVideo()) {
            return 'waiting-video';
        }

        if ($show->shouldDisplayWaitingImage()) {
            return 'waiting-image';
        }

        return 'waiting-text';
    }
}
