<div class="w-full max-w-2xl">
    <h1 class="text-xl sm:text-2xl font-bold mb-5 sm:mb-6">Νέο Talent Show</h1>
    <form wire:submit="save" class="card space-y-4">
        <div>
            <label for="show-title" class="block text-sm font-medium mb-1">Τίτλος *</label>
            <input id="show-title" type="text" wire:model.live="title" class="input-touch" required>
            @error('title') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="show-slug" class="block text-sm font-medium mb-1">Slug *</label>
            <input id="show-slug" type="text" wire:model="slug" class="input-touch" required>
            @error('slug') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="show-desc" class="block text-sm font-medium mb-1">Περιγραφή</label>
            <textarea id="show-desc" wire:model="description" class="input-touch min-h-[100px]" rows="3"></textarea>
        </div>
        <div>
            <label for="show-venue" class="block text-sm font-medium mb-1">Τοποθεσία</label>
            <input id="show-venue" type="text" wire:model="venue" class="input-touch">
        </div>
        <div>
            <label for="show-date" class="block text-sm font-medium mb-1">Ημερομηνία</label>
            <input id="show-date" type="date" wire:model="event_date" class="input-touch">
        </div>
        <button type="submit" class="w-full sm:w-auto btn-touch bg-indigo-600 text-white hover:bg-indigo-700">Δημιουργία</button>
    </form>
</div>
