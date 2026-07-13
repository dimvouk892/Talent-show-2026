<?php

namespace App\Livewire\Admin\TalentShows;

use App\Models\TalentShow;
use App\Services\TalentShowManagementService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public ?int $confirmDeleteId = null;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function askDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function confirmDelete(TalentShowManagementService $managementService): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }

        $show = TalentShow::findOrFail($this->confirmDeleteId);
        $this->authorize('delete', $show);

        $managementService->delete($show);

        $this->confirmDeleteId = null;
        $this->flashSuccess = 'Το Talent Show διαγράφηκε.';
        $this->flashError = null;
    }

    public function render()
    {
        $shows = TalentShow::latest()->paginate(10);

        $confirmDeleteShow = $this->confirmDeleteId
            ? TalentShow::find($this->confirmDeleteId)
            : null;

        return view('livewire.admin.talent-shows.index', [
            'shows' => $shows,
            'confirmDeleteShow' => $confirmDeleteShow,
        ]);
    }
}
