<?php

namespace App\Livewire\Presentation;

use App\Models\TalentShow;
use App\Services\ResultsService;
use App\Services\ScoreCalculationService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.presentation')]
class ShowScreen extends Component
{
    public TalentShow $talentShow;

    public function mount(): void
    {
        $this->talentShow = TalentShow::forMonitor();
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

        if ($winner && $show->winner_revealed) {
            return 'winner';
        }

        if ($show->show_ranking && count($ranking) > 0) {
            return 'ranking';
        }

        if ($currentTeam) {
            return 'team-'.$currentTeam->id;
        }

        return 'waiting-text';
    }
}
