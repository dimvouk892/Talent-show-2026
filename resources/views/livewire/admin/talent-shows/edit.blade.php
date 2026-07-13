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
        <div>
            <label for="edit-opening-video" class="block text-sm font-medium mb-1">Video έναρξης (όταν πατάτε «Έναρξη Talent Show»)</label>
            <input id="edit-opening-video" type="file" wire:model="opening_video" accept="video/mp4,video/webm,video/quicktime" class="w-full text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700">
            <p class="text-xs text-gray-500 mt-1">MP4, WebM ή MOV · μέχρι 20 MB</p>
            @if ($talentShow->opening_video_path)
                <p class="text-xs text-indigo-600 mt-1">Υπάρχει video έναρξης</p>
            @endif
            @error('opening_video') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="edit-closing-video" class="block text-sm font-medium mb-1">Video λήξης (όταν πατάτε «Ολοκλήρωση Talent Show»)</label>
            <input id="edit-closing-video" type="file" wire:model="closing_video" accept="video/mp4,video/webm,video/quicktime" class="w-full text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700">
            <p class="text-xs text-gray-500 mt-1">MP4, WebM ή MOV · μέχρι 20 MB</p>
            @if ($talentShow->closing_video_path)
                <p class="text-xs text-indigo-600 mt-1">Υπάρχει video λήξης</p>
            @endif
            @error('closing_video') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="edit-waiting-video" class="block text-sm font-medium mb-1">Video αναμονής (οθόνη «Αναμονή έναρξης»)</label>
            <input id="edit-waiting-video" type="file" wire:model="waiting_video" accept="video/mp4,video/webm,video/quicktime" class="w-full text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700">
            <p class="text-xs text-gray-500 mt-1">MP4, WebM ή MOV · μέχρι 20 MB · εμφανίζεται αυτόματα στην οθόνη αναμονής</p>
            @if ($talentShow->waiting_video_path)
                <p class="text-xs text-indigo-600 mt-1">Υπάρχει video αναμονής</p>
            @endif
            @error('waiting_video') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="edit-waiting-image" class="block text-sm font-medium mb-1">Εικόνα αναμονής (οθόνη «Αναμονή έναρξης»)</label>
            <input id="edit-waiting-image" type="file" wire:model="waiting_image" accept="image/*" class="w-full text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700">
            <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP · μέχρι 5 MB · εμφανίζεται αυτόματα στην οθόνη αναμονής (αν δεν υπάρχει video)</p>
            @if ($talentShow->waiting_image_path)
                <p class="text-xs text-indigo-600 mt-1">Υπάρχει εικόνα αναμονής</p>
            @endif
            @error('waiting_image') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <button type="submit" class="w-full sm:w-auto btn-touch bg-indigo-600 text-white">Αποθήκευση</button>
            <a href="{{ route('admin.talent-shows.show', $talentShow) }}" class="w-full sm:w-auto btn-touch border border-gray-300 text-center">Ακύρωση</a>
        </div>
    </form>
</div>
