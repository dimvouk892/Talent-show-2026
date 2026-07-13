<?php

namespace App\Livewire\Admin\TalentShows;

use App\Models\TalentShow;
use Illuminate\Support\Facades\Storage;
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

    public $opening_video = null;

    public $closing_video = null;

    public $waiting_video = null;

    public $waiting_image = null;

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
            'opening_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:20480'],
            'closing_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:20480'],
            'waiting_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:20480'],
            'waiting_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $data = [
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'venue' => $this->venue ?: null,
            'event_date' => $this->event_date,
        ];

        if ($this->opening_video) {
            $path = $this->opening_video->store('talent-shows/'.$this->talentShow->id.'/videos', 'public');
            if ($this->talentShow->opening_video_path) {
                Storage::disk('public')->delete($this->talentShow->opening_video_path);
            }
            $data['opening_video_path'] = $path;
        }

        if ($this->closing_video) {
            $path = $this->closing_video->store('talent-shows/'.$this->talentShow->id.'/videos', 'public');
            if ($this->talentShow->closing_video_path) {
                Storage::disk('public')->delete($this->talentShow->closing_video_path);
            }
            $data['closing_video_path'] = $path;
        }

        if ($this->waiting_video) {
            $path = $this->waiting_video->store('talent-shows/'.$this->talentShow->id.'/videos', 'public');
            if ($this->talentShow->waiting_video_path) {
                Storage::disk('public')->delete($this->talentShow->waiting_video_path);
            }
            $data['waiting_video_path'] = $path;
            $data['showing_waiting_video'] = true;
            $data['showing_waiting_image'] = false;
        }

        if ($this->waiting_image) {
            $path = $this->waiting_image->store('talent-shows/'.$this->talentShow->id.'/images', 'public');
            if ($this->talentShow->waiting_image_path) {
                Storage::disk('public')->delete($this->talentShow->waiting_image_path);
            }
            $data['waiting_image_path'] = $path;
            if (! isset($data['waiting_video_path']) && ! $this->talentShow->waiting_video_path) {
                $data['showing_waiting_image'] = true;
                $data['showing_waiting_video'] = false;
            }
        }

        $this->talentShow->update($data);

        session()->flash('success', 'Το Talent Show ενημερώθηκε.');

        $this->redirect(route('admin.talent-shows.show', $this->talentShow), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.talent-shows.edit');
    }
}
