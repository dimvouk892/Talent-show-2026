<?php

namespace App\Livewire\Admin;

use App\Models\TalentShow;
use App\Services\TalentShowControlService;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ScreenVideos extends Component
{
    public int $talentShowId;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(TalentShow $talentShow): void
    {
        $this->authorize('control', $talentShow);
        $this->talentShowId = $talentShow->id;
    }

    protected function getTalentShow(): TalentShow
    {
        return TalentShow::with(['currentTeam'])->findOrFail($this->talentShowId);
    }

    protected function notifySuccess(string $message): void
    {
        $this->flashSuccess = $message;
        $this->flashError = null;
    }

    protected function notifyError(string $message): void
    {
        $this->flashError = $message;
        $this->flashSuccess = null;
    }

    public function pollVideoState(): void
    {
        // Trigger re-render for live playback status updates.
    }

    public function dismissTeamIntro(TalentShowControlService $control): void
    {
        $control->dismissTeamIntro($this->getTalentShow());
        $this->notifySuccess('Ξεκίνησε η παρουσίαση της ομάδας.');
    }

    public function replayTeamIntro(TalentShowControlService $control): void
    {
        try {
            $control->replayTeamIntro($this->getTalentShow());
            $this->notifySuccess('Προβολή intro ομάδας στην οθόνη.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function dismissOpeningVideo(TalentShowControlService $control): void
    {
        $control->dismissOpeningVideo($this->getTalentShow());
        $this->notifySuccess('Το video έναρξης ολοκληρώθηκε.');
    }

    public function replayOpeningVideo(TalentShowControlService $control): void
    {
        try {
            $control->replayOpeningVideo($this->getTalentShow());
            $this->notifySuccess('Προβολή intro εισαγωγής στην οθόνη.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function dismissClosingVideo(TalentShowControlService $control): void
    {
        $control->dismissClosingVideo($this->getTalentShow());
        $this->notifySuccess('Το video λήξης ολοκληρώθηκε.');
    }

    public function replayClosingVideo(TalentShowControlService $control): void
    {
        try {
            $control->replayClosingVideo($this->getTalentShow());
            $this->notifySuccess('Προβολή τελικού video στην οθόνη.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function dismissWaitingVideo(TalentShowControlService $control): void
    {
        $control->dismissWaitingVideo($this->getTalentShow());
        $this->notifySuccess('Το video αναμονής σταμάτησε.');
    }

    public function replayWaitingVideo(TalentShowControlService $control): void
    {
        try {
            $control->replayWaitingVideo($this->getTalentShow());
            $this->notifySuccess('Προβολή video αναμονής στην οθόνη.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function dismissWaitingImage(TalentShowControlService $control): void
    {
        $control->dismissWaitingImage($this->getTalentShow());
        $this->notifySuccess('Η εικόνα αναμονής αφαιρέθηκε από την οθόνη.');
    }

    public function showWaitingImage(TalentShowControlService $control): void
    {
        try {
            $control->showWaitingImage($this->getTalentShow());
            $this->notifySuccess('Προβολή εικόνας αναμονής στην οθόνη.');
        } catch (InvalidArgumentException $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function render()
    {
        $talentShow = $this->getTalentShow();

        return view('livewire.admin.screen-videos', [
            'talentShow' => $talentShow,
            'currentTeam' => $talentShow->currentTeam,
            'teamsWithVideo' => $talentShow->teams()->whereNotNull('video_path')->ordered()->get(),
        ]);
    }
}
