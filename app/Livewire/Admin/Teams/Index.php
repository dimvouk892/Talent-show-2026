<?php

namespace App\Livewire\Admin\Teams;

use App\Models\TalentShow;
use App\Models\Team;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public TalentShow $talentShow;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public int $display_order = 0;

    public bool $is_active = true;

    public function mount(TalentShow $talentShow): void
    {
        $this->authorize('view', $talentShow);
        $this->talentShow = $talentShow;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->display_order = ($this->talentShow->teams()->max('display_order') ?? 0) + 1;
    }

    public function edit(int $id): void
    {
        $team = Team::where('talent_show_id', $this->talentShow->id)->findOrFail($id);
        $this->editingId = $team->id;
        $this->name = $team->name;
        $this->code = $team->code ?? '';
        $this->description = $team->description ?? '';
        $this->display_order = $team->display_order;
        $this->is_active = $team->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'display_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $data = [
            'name' => $this->name,
            'code' => $this->code ?: null,
            'description' => $this->description ?: null,
            'display_order' => $this->display_order,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Team::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Η ομάδα ενημερώθηκε.');
        } else {
            $this->talentShow->teams()->create($data);
            session()->flash('success', 'Η ομάδα δημιουργήθηκε.');
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $team = Team::where('talent_show_id', $this->talentShow->id)->findOrFail($id);

        if ($team->hasVotes()) {
            session()->flash('error', 'Δεν μπορείτε να διαγράψετε ομάδα με ψήφους.');

            return;
        }

        $team->delete();
        session()->flash('success', 'Η ομάδα διαγράφηκε.');
    }

    public function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->display_order = 0;
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.admin.teams.index', [
            'teams' => $this->talentShow->teams()->ordered()->get(),
        ]);
    }
}
