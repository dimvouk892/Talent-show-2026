<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <h1 class="text-xl sm:text-2xl font-bold">Πίνακας Ελέγχου</h1>
        <a href="{{ route('admin.talent-shows.create') }}" class="w-full sm:w-auto btn-touch bg-indigo-600 text-white text-center hover:bg-indigo-700">
            Νέο Talent Show
        </a>
    </div>

    @if ($flashSuccess)
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-xl text-sm sm:text-base" role="status">{{ $flashSuccess }}</div>
    @endif
    @if ($flashError)
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-xl text-sm sm:text-base" role="alert">{{ $flashError }}</div>
    @endif

    @forelse ($showData as $data)
        <article class="card mb-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-lg sm:text-xl font-bold break-words">
                        <a href="{{ route('admin.talent-shows.show', $data['show']) }}" class="text-indigo-600 hover:underline">
                            {{ $data['show']->title }}
                        </a>
                    </h2>
                    <p class="text-gray-500 mt-1 text-sm sm:text-base">{{ $data['show']->status->label() }}</p>
                    @if ($data['show']->event_date)
                        <p class="text-sm text-gray-500">{{ $data['show']->event_date->format('d/m/Y') }} — {{ $data['show']->venue }}</p>
                    @endif
                </div>
                <div class="grid grid-cols-3 gap-2 sm:gap-3 text-center sm:text-right text-sm text-gray-600 shrink-0">
                    <div class="bg-gray-50 rounded-lg p-2"><span class="block font-bold text-gray-900">{{ $data['teams_count'] }}</span>Ομάδες</div>
                    <div class="bg-gray-50 rounded-lg p-2"><span class="block font-bold text-gray-900">{{ $data['judges_count'] }}</span>Κριτές</div>
                    <div class="bg-gray-50 rounded-lg p-2"><span class="block font-bold text-gray-900">{{ $data['connected_judges'] }}</span>Online</div>
                </div>
            </div>

            @if ($data['current_team'])
                <div class="mt-4 p-3 sm:p-4 bg-indigo-50 rounded-xl">
                    <p class="font-medium text-sm sm:text-base">Τρέχουσα ομάδα: {{ $data['current_team']->name }}</p>
                    <p class="text-sm">Ψήφισαν {{ $data['votes_progress']['voted'] }} από {{ $data['votes_progress']['total'] }}</p>
                </div>
            @endif

            <div class="mt-4">
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-indigo-600 h-2.5 rounded-full transition-all" style="width: {{ $data['overall_progress']['percentage'] }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Πρόοδος: {{ $data['overall_progress']['completed_teams'] }}/{{ $data['overall_progress']['total_teams'] }} ομάδες</p>
            </div>

            <div class="mt-4 grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                <a href="{{ route('admin.talent-shows.edit', $data['show']) }}" class="btn-touch-sm border border-gray-200 text-center">Επεξεργασία</a>
                <button type="button" wire:click="askDelete({{ $data['show']->id }})" class="btn-touch-sm border border-red-200 text-red-600">Διαγραφή</button>
                <a href="{{ route('admin.talent-shows.teams', $data['show']) }}" class="btn-touch-sm bg-gray-100 text-center">Ομάδες</a>
                <a href="{{ route('admin.talent-shows.judges', $data['show']) }}" class="btn-touch-sm bg-gray-100 text-center">Κριτές</a>
                <a href="{{ route('admin.talent-shows.live-control', $data['show']) }}" class="btn-touch-sm bg-indigo-100 text-indigo-700 text-center sm:col-span-1 col-span-2">Ζωντανός έλεγχος</a>
                <a href="{{ route('admin.talent-shows.results', $data['show']) }}" class="btn-touch-sm bg-gray-100 text-center">Αποτελέσματα</a>
                <a href="{{ route('presentation.show') }}" target="_blank" rel="noopener" class="btn-touch-sm bg-gray-800 text-white text-center">Monitor ↗</a>
                <a href="{{ route('presentation.panel') }}" target="_blank" rel="noopener" class="btn-touch-sm bg-indigo-600 text-white text-center border border-indigo-500">Panel ↗</a>
            </div>
        </article>
    @empty
        <p class="text-gray-500 text-center py-8">Δεν υπάρχουν Talent Shows. Δημιουργήστε ένα νέο.</p>
    @endforelse

    @if ($confirmDeleteShow)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="delete-show-title">
            <div class="modal-panel">
                <h3 id="delete-show-title" class="font-bold text-lg mb-3">Διαγραφή Talent Show;</h3>
                <p class="text-sm text-gray-600 mb-2">
                    Θα διαγραφεί οριστικά το <strong>{{ $confirmDeleteShow->title }}</strong> μαζί με ομάδες, κριτές και ψήφους.
                </p>
                @if ($confirmDeleteShow->votes()->exists())
                    <p class="text-sm text-orange-700 mb-5">Προσοχή: υπάρχουν καταχωρημένες ψήφοι σε αυτή την εκδήλωση.</p>
                @else
                    <p class="text-sm text-gray-500 mb-5">Η ενέργεια δεν αναιρείται.</p>
                @endif
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" wire:click="confirmDelete" class="w-full btn-touch bg-red-600 text-white">Ναι, διαγραφή</button>
                    <button type="button" wire:click="cancelDelete" class="w-full btn-touch border border-gray-300">Όχι</button>
                </div>
            </div>
        </div>
    @endif
</div>
