<?php

namespace App\Livewire\Admin\Teams;

use App\Models\TalentShow;
use App\Models\Team;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithFileUploads;

    public TalentShow $talentShow;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public $photo = null;

    public $video = null;

    public int $display_order = 0;

    public bool $is_active = true;

    public bool $clearPhoto = false;

    public bool $clearVideo = false;

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
        $this->photo = null;
        $this->video = null;
        $this->clearPhoto = false;
        $this->clearVideo = false;
        $this->showForm = true;
    }

    public function removeExistingPhoto(): void
    {
        $this->clearPhoto = true;
        $this->photo = null;
    }

    public function removeExistingVideo(): void
    {
        $this->clearVideo = true;
        $this->video = null;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:20480'],
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

        if ($this->photo) {
            $path = $this->photo->store('teams/'.$this->talentShow->id, 'public');
            $data['photo_path'] = $path;
        }

        if ($this->video) {
            $path = $this->video->store('teams/'.$this->talentShow->id.'/videos', 'public');
            $data['video_path'] = $path;
        }

        if ($this->editingId) {
            $team = Team::findOrFail($this->editingId);
            if ($this->clearPhoto && $team->photo_path) {
                Storage::disk('public')->delete($team->photo_path);
                $data['photo_path'] = null;
            }
            if ($this->clearVideo && $team->video_path) {
                Storage::disk('public')->delete($team->video_path);
                $data['video_path'] = null;
            }
            if (isset($data['photo_path']) && $team->photo_path) {
                Storage::disk('public')->delete($team->photo_path);
            }
            if (isset($data['video_path']) && $team->video_path) {
                Storage::disk('public')->delete($team->video_path);
            }
            $team->update($data);
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

        if ($team->photo_path) {
            Storage::disk('public')->delete($team->photo_path);
        }

        if ($team->video_path) {
            Storage::disk('public')->delete($team->video_path);
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
        $this->photo = null;
        $this->video = null;
        $this->clearPhoto = false;
        $this->clearVideo = false;
        $this->display_order = 0;
        $this->is_active = true;
    }

    public function render()
    {
        $editingTeam = $this->editingId
            ? Team::where('talent_show_id', $this->talentShow->id)->find($this->editingId)
            : null;

        return view('livewire.admin.teams.index', [
            'teams' => $this->talentShow->teams()->ordered()->get(),
            'editingTeam' => $editingTeam,
        ]);
    }
}
