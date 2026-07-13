<?php

namespace App\Livewire\Admin\TalentShows;

use App\Models\TalentShow;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Create extends Component
{
    public string $title = '';

    public string $slug = '';

    public string $description = '';

    public string $venue = '';

    public ?string $event_date = null;

    public function updatedTitle(string $value): void
    {
        if (empty($this->slug)) {
            $this->slug = Str::slug($value);
        }
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:talent_shows,slug'],
            'description' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:255'],
            'event_date' => ['nullable', 'date'],
        ]);

        $show = TalentShow::create([
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'venue' => $this->venue ?: null,
            'event_date' => $this->event_date,
            'created_by' => auth()->id(),
        ]);

        session()->flash('success', 'Το Talent Show δημιουργήθηκε.');

        $this->redirect(route('admin.talent-shows.show', $show), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.talent-shows.create');
    }
}
