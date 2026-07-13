<?php

namespace App\Livewire\Admin;

use App\Models\TalentShow;
use App\Services\ResultsService;
use App\Services\TalentShowControlService;
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

    public function render(ResultsService $resultsService)
    {
        $talentShow = $this->getTalentShow();
        $report = $resultsService->getDetailedReport($talentShow);
        $tiedTeams = $resultsService->getTiedTeams($talentShow);
        $winner = $resultsService->getWinner($talentShow);

        return view('livewire.admin.results', [
            'talentShow' => $talentShow,
            'summary' => $report['summary'],
            'judges' => $report['judges'],
            'ranking' => $report['ranking'],
            'tiedTeams' => $tiedTeams,
            'winner' => $winner,
        ]);
    }
}
