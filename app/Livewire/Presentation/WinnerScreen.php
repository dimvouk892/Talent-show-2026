<?php

namespace App\Livewire\Presentation;

use App\Models\TalentShow;
use App\Services\ResultsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.presentation')]
class WinnerScreen extends Component
{
    public TalentShow $talentShow;

    public function mount(): void
    {
        $this->talentShow = TalentShow::forMonitor();
    }

    public function render(ResultsService $resultsService)
    {
        $this->talentShow->refresh();

        return view('livewire.presentation.winner-screen', [
            'winner' => $this->talentShow->winner_revealed
                ? $resultsService->getWinner($this->talentShow)
                : null,
            'revealed' => $this->talentShow->winner_revealed,
        ]);
    }
}
