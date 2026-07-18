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
            || $this->talentShow->show_final_overview
            || $this->talentShow->show_final_chart
            ? array_values(array_filter(
                $resultsService->getRanking($this->talentShow),
                fn ($item) => $item['is_complete']
            ))
            : [];
        $podium = $resultsService->getPodiumRevealState($this->talentShow);
        $winner = $resultsService->getWinner($this->talentShow);

        $scoreboardJudges = collect();
        $scoreboardRanking = [];
        if ($this->talentShow->show_scoreboard) {
            $report = $resultsService->getDetailedReport($this->talentShow);
            $scoreboardJudges = $report['judges'];
            $scoreboardRanking = $report['ranking'];
        }

        return view('livewire.presentation.show-screen', [
            'currentTeam' => $currentTeam,
            'scores' => $scores,
            'voteProgress' => $voteProgress,
            'judgeStatus' => $judgeStatus,
            'ranking' => $ranking,
            'podium' => $podium,
            'winner' => $winner,
            'scoreboardJudges' => $scoreboardJudges,
            'scoreboardRanking' => $scoreboardRanking,
            'presentationScene' => $this->presentationSceneKey($currentTeam, $ranking, $podium),
        ]);
    }

    protected function presentationSceneKey($currentTeam, array $ranking, array $podium): string
    {
        $show = $this->talentShow;

        if ($show->show_scoreboard) {
            return 'scoreboard';
        }

        if ($show->show_final_overview || $show->show_final_chart) {
            return 'final-'.($show->show_final_overview ? 'rank' : '').($show->show_final_chart ? 'chart' : '');
        }

        if (($podium['step'] ?? 0) > 0) {
            return 'podium-'.$podium['step'].'-'.($podium['current']['team']->id ?? 0);
        }

        if ($show->show_ranking) {
            return 'awaiting-podium';
        }

        if ($currentTeam) {
            return 'team-'.$currentTeam->id;
        }

        return 'waiting-text';
    }
}
