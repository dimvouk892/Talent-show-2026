<?php

namespace App\Livewire\Admin\TalentShows;

use App\Models\TalentShow;
use App\Services\TalentShowControlService;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class Edit extends Component
{
    use WithFileUploads;

    public TalentShow $talentShow;

    public string $title = '';

    public string $slug = '';

    public string $description = '';

    public string $venue = '';

    public ?string $event_date = null;

    public $presentationBackground = null;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public bool $showClearScoresConfirm = false;

    public function mount(TalentShow $talentShow): void
    {
        $this->authorize('update', $talentShow);
        $this->talentShow = $talentShow;
        $this->title = $talentShow->title;
        $this->slug = $talentShow->slug;
        $this->description = $talentShow->description ?? '';
        $this->venue = $talentShow->venue ?? '';
        $this->event_date = $talentShow->event_date?->format('Y-m-d');
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:talent_shows,slug,'.$this->talentShow->id],
            'description' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:255'],
            'event_date' => ['nullable', 'date'],
        ]);

        $this->talentShow->update([
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'venue' => $this->venue ?: null,
            'event_date' => $this->event_date,
        ]);

        session()->flash('success', 'Το Talent Show ενημερώθηκε.');

        $this->redirect(route('admin.talent-shows.show', $this->talentShow), navigate: true);
    }

    public function savePresentationBackground(TalentShowControlService $control): void
    {
        $this->validate([
            'presentationBackground' => [
                'required',
                'file',
                'max:524288',
                'mimes:jpeg,jpg,png,webp,gif,mp4,webm,mov,m4v,qt',
            ],
        ], [
            'presentationBackground.required' => 'Επιλέξτε εικόνα ή βίντεο.',
            'presentationBackground.max' => 'Μέγιστο μέγεθος 512MB.',
            'presentationBackground.mimes' => 'Επιτρέπονται jpg, png, webp, gif, mp4, webm, mov.',
            'presentationBackground.uploaded' => 'Αποτυχία ανεβάσματος. Το αρχείο είναι πολύ μεγάλο ή έληξε το χρονικό όριο του server.',
        ]);

        try {
            $this->talentShow = $control->storePresentationBackground($this->talentShow, $this->presentationBackground);
            $this->presentationBackground = null;
            $this->flashSuccess = 'Αποθηκεύτηκε το φόντο παρουσίασης.';
            $this->flashError = null;
        } catch (InvalidArgumentException $e) {
            $this->flashError = $e->getMessage();
            $this->flashSuccess = null;
        }
    }

    public function removePresentationBackground(TalentShowControlService $control): void
    {
        $this->talentShow = $control->removePresentationBackground($this->talentShow);
        $this->presentationBackground = null;
        $this->flashSuccess = 'Αφαιρέθηκε το φόντο παρουσίασης.';
        $this->flashError = null;
    }

    public function askClearScores(): void
    {
        $this->showClearScoresConfirm = true;
    }

    public function cancelClearScores(): void
    {
        $this->showClearScoresConfirm = false;
    }

    public function confirmClearScores(TalentShowControlService $control): void
    {
        try {
            $this->authorize('control', $this->talentShow);
            $this->talentShow = $control->clearScores($this->talentShow->fresh());
            $this->showClearScoresConfirm = false;
            $this->flashSuccess = 'Οι βαθμολογίες διαγράφηκαν. Η εκδήλωση είναι σε αναμονή — πατήστε «Έναρξη» στον Ζωντανό Έλεγχο.';
            $this->flashError = null;
        } catch (InvalidArgumentException $e) {
            $this->flashError = $e->getMessage();
            $this->flashSuccess = null;
            $this->showClearScoresConfirm = false;
        }
    }

    public function render()
    {
        $this->talentShow->refresh();

        return view('livewire.admin.talent-shows.edit');
    }
}
