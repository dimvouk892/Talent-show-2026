<div>
    @include('partials.admin-show-nav', ['talentShow' => $talentShow])

    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
        <h1 class="text-lg sm:text-xl font-bold">Ομάδες</h1>
        <button type="button" wire:click="openCreate" class="w-full sm:w-auto btn-touch bg-indigo-600 text-white">Νέα ομάδα</button>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="card mb-6 space-y-4">
            <h2 class="font-semibold text-base">
                {{ $editingId ? 'Επεξεργασία ομάδας' : 'Νέα ομάδα' }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="team-name" class="block text-sm font-medium mb-1">Όνομα *</label>
                    <input id="team-name" type="text" wire:model="name" class="input-touch" required>
                    @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="team-code" class="block text-sm font-medium mb-1">Κωδικός</label>
                    <input id="team-code" type="text" wire:model="code" class="input-touch">
                </div>
                <div class="md:col-span-2">
                    <label for="team-desc" class="block text-sm font-medium mb-1">Περιγραφή</label>
                    <textarea id="team-desc" wire:model="description" class="input-touch min-h-[80px]" rows="2"></textarea>
                </div>
                <div>
                    <label for="team-order" class="block text-sm font-medium mb-1">Σειρά εμφάνισης</label>
                    <input id="team-order" type="number" wire:model="display_order" min="0" class="input-touch">
                </div>
                <label class="flex items-center gap-3 min-h-12">
                    <input type="checkbox" wire:model="is_active" class="w-5 h-5 rounded">
                    Ενεργή
                </label>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <button type="submit" class="w-full sm:w-auto btn-touch bg-indigo-600 text-white">Αποθήκευση</button>
                <button type="button" wire:click="resetForm" class="w-full sm:w-auto btn-touch border border-gray-300">Ακύρωση</button>
            </div>
        </form>
    @endif

    <div class="grid gap-3 sm:gap-4">
        @foreach ($teams as $team)
            <article class="card flex flex-col sm:flex-row gap-4 sm:items-center">
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold break-words">{{ $team->name }} @if($team->code)<span class="text-gray-400 text-sm">({{ $team->code }})</span>@endif</h3>
                    <p class="text-sm text-gray-500">Σειρά {{ $team->display_order }} · {{ $team->status->label() }} · {{ $team->is_active ? 'Ενεργή' : 'Ανενεργή' }}</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <button type="button" wire:click="edit({{ $team->id }})" class="w-full sm:w-auto btn-touch-sm border border-gray-200 text-indigo-600">Επεξεργασία</button>
                    <button type="button" wire:click="delete({{ $team->id }})" wire:confirm="Διαγραφή ομάδας;" class="w-full sm:w-auto btn-touch-sm border border-red-200 text-red-600">Διαγραφή</button>
                </div>
            </article>
        @endforeach
    </div>
</div>
