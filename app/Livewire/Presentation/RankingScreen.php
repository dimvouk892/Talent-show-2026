<?php

namespace App\Livewire\Presentation;

use App\Models\TalentShow;
use App\Services\ResultsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.presentation')]
class RankingScreen extends Component
{
    public TalentShow $talentShow;

    public function mount(): void
    {
        $this->talentShow = TalentShow::forMonitor();
    }

    public function render(ResultsService $resultsService)
    {
        $this->talentShow->refresh();

        return view('livewire.presentation.ranking-screen', [
            'ranking' => $this->talentShow->show_ranking
                ? array_values(array_filter(
                    $resultsService->getRanking($this->talentShow),
                    fn ($item) => $item['is_complete']
                ))
                : [],
            'showRanking' => $this->talentShow->show_ranking,
        ]);
    }
}
