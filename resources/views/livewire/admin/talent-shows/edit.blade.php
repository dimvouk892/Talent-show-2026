<div class="w-full max-w-2xl">
    <h1 class="text-xl sm:text-2xl font-bold mb-5 sm:mb-6">Επεξεργασία Talent Show</h1>
    <form wire:submit="save" class="card space-y-4">
        <div>
            <label for="edit-title" class="block text-sm font-medium mb-1">Τίτλος *</label>
            <input id="edit-title" type="text" wire:model="title" class="input-touch" required>
            @error('title') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="edit-slug" class="block text-sm font-medium mb-1">Slug *</label>
            <input id="edit-slug" type="text" wire:model="slug" class="input-touch" required>
            @error('slug') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="edit-desc" class="block text-sm font-medium mb-1">Περιγραφή</label>
            <textarea id="edit-desc" wire:model="description" class="input-touch min-h-[100px]" rows="3"></textarea>
        </div>
        <div>
            <label for="edit-venue" class="block text-sm font-medium mb-1">Τοποθεσία</label>
            <input id="edit-venue" type="text" wire:model="venue" class="input-touch">
        </div>
        <div>
            <label for="edit-date" class="block text-sm font-medium mb-1">Ημερομηνία</label>
            <input id="edit-date" type="date" wire:model="event_date" class="input-touch">
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <button type="submit" class="w-full sm:w-auto btn-touch bg-indigo-600 text-white">Αποθήκευση</button>
            <a href="{{ route('admin.talent-shows.show', $talentShow) }}" class="w-full sm:w-auto btn-touch border border-gray-300 text-center">Ακύρωση</a>
        </div>
    </form>
</div>
