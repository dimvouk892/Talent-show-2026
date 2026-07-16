<?php

namespace App\Livewire\Presentation;

use App\Models\TalentShow;
use App\Services\ResultsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.presentation')]
class PanelScreen extends Component
{
    public TalentShow $talentShow;

    public function mount(): void
    {
        $this->talentShow = TalentShow::forMonitor();
    }

    public function pollPanel(): void
    {
        // Re-render for live scoreboard updates.
    }

    public function render(ResultsService $resultsService)
    {
        $this->talentShow->refresh();

        $report = $resultsService->getDetailedReport($this->talentShow);
        $winner = $resultsService->getWinner($this->talentShow);

        return view('livewire.presentation.panel-screen', [
            'summary' => $report['summary'],
            'judges' => $report['judges'],
            'ranking' => $report['ranking'],
            'winner' => $winner,
        ]);
    }
}
