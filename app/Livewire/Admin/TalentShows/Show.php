<?php

namespace App\Livewire\Admin\TalentShows;

use App\Models\TalentShow;
use App\Services\JudgeAccessService;
use App\Services\ResultsService;
use App\Services\ScoreCalculationService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Show extends Component
{
    public TalentShow $talentShow;

    public function mount(TalentShow $talentShow): void
    {
        $this->authorize('view', $talentShow);
        $this->talentShow = $talentShow;
    }

    public function render(
        JudgeAccessService $judgeAccessService,
        ScoreCalculationService $scoreCalculationService,
        ResultsService $resultsService,
    ) {
        $this->talentShow->refresh();

        $voteProgress = $scoreCalculationService->votesProgress($this->talentShow);
        $progress = $resultsService->getProgress($this->talentShow);

        return view('livewire.admin.talent-shows.show', [
            'teamsCount' => $this->talentShow->activeTeams()->count(),
            'judgesCount' => $this->talentShow->activeJudges()->count(),
            'connectedJudges' => $judgeAccessService->getConnectedJudgesCount($this->talentShow),
            'voteProgress' => $voteProgress,
            'progress' => $progress,
        ]);
    }
}
