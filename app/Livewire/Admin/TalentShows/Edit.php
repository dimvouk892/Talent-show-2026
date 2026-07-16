<?php

namespace App\Livewire\Admin\TalentShows;

use App\Models\TalentShow;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Edit extends Component
{
    public TalentShow $talentShow;

    public string $title = '';

    public string $slug = '';

    public string $description = '';

    public string $venue = '';

    public ?string $event_date = null;

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

    public function render()
    {
        return view('livewire.admin.talent-shows.edit');
    }
}
