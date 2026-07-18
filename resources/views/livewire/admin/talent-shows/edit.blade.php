<div class="w-full max-w-2xl">
    @include('partials.admin-show-nav', ['talentShow' => $talentShow])

    <h1 class="text-xl sm:text-2xl font-bold mb-5 sm:mb-6">Ρυθμίσεις Talent Show</h1>

    @if ($flashSuccess)
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-xl text-sm" role="status">{{ $flashSuccess }}</div>
    @endif
    @if ($flashError)
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-xl text-sm" role="alert">{{ $flashError }}</div>
    @endif

    <form wire:submit="save" class="card space-y-4 mb-5">
        <h2 class="text-lg font-bold">Βασικά στοιχεία</h2>
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

    <section class="card space-y-3" aria-label="Φόντο παρουσίασης"
             x-data="{ uploading: false, progress: 0 }">
        <h2 class="text-lg font-bold">Φόντο monitor</h2>
        <p class="text-sm text-gray-500">Εικόνα ή βίντεο (mp4/webm) ως φόντο στις οθόνες παρουσίασης. Προτεινόμενο έως ~200MB για σταθερό ανέβασμα στο Hostinger (μέγιστο 512MB).</p>

        @if ($talentShow->hasPresentationBackground())
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm space-y-2">
                <p>Ενεργό: <strong>{{ $talentShow->presentation_bg_type === 'video' ? 'Βίντεο' : 'Εικόνα' }}</strong></p>
                @if ($talentShow->presentation_bg_type === 'image')
                    <img src="{{ $talentShow->presentationBackgroundUrl() }}" alt="" class="max-h-40 rounded-lg object-cover w-full">
                @else
                    <video class="max-h-48 w-full rounded-lg object-cover bg-black"
                           src="{{ $talentShow->presentationBackgroundUrl() }}"
                           controls muted playsinline preload="metadata"></video>
                @endif
                <a href="{{ $talentShow->presentationBackgroundUrl() }}" target="_blank" rel="noopener" class="text-indigo-700 underline text-xs">Άνοιγμα αρχείου ↗</a>
                <button type="button" wire:click="removePresentationBackground" class="w-full btn-touch border border-red-300 text-red-700 hover:bg-red-50">
                    Αφαίρεση φόντου
                </button>
            </div>
        @endif

        <div>
            <label for="presentation-bg" class="block text-sm font-medium mb-1">Ανέβασμα αρχείου</label>
            <input id="presentation-bg"
                   type="file"
                   wire:model="presentationBackground"
                   accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov,.m4v"
                   x-on:livewire-upload-start="uploading = true; progress = 0"
                   x-on:livewire-upload-progress="progress = $event.detail.progress"
                   x-on:livewire-upload-finish="uploading = false; progress = 100"
                   x-on:livewire-upload-error="uploading = false; progress = 0"
                   class="input-touch">
            @error('presentationBackground') <span class="text-red-600 text-sm block mt-1">{{ $message }}</span> @enderror
            <div wire:loading wire:target="presentationBackground" class="text-sm text-indigo-700 mt-1">Μεταφόρτωση προσωρινού αρχείου…</div>
            <div x-show="uploading" x-cloak class="mt-2">
                <div class="h-2 rounded-full bg-gray-200 overflow-hidden">
                    <div class="h-2 bg-indigo-600 transition-all" :style="'width:' + progress + '%'"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1" x-text="'Πρόοδος: ' + progress + '%'"></p>
            </div>
            @if ($presentationBackground)
                <p class="text-sm text-green-700 mt-2">Το αρχείο φορτώθηκε — πατήστε «Αποθήκευση φόντου».</p>
            @endif
        </div>
        <button type="button"
                wire:click="savePresentationBackground"
                wire:loading.attr="disabled"
                wire:target="savePresentationBackground,presentationBackground"
                class="w-full btn-touch bg-gray-800 text-white hover:bg-gray-700 disabled:opacity-40">
            <span wire:loading.remove wire:target="savePresentationBackground">Αποθήκευση φόντου</span>
            <span wire:loading wire:target="savePresentationBackground">Αποθήκευση…</span>
        </button>
    </section>
</div>
