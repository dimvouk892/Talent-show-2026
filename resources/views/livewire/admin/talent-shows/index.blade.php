<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
        <h1 class="text-xl sm:text-2xl font-bold">Talent Shows</h1>
        <a href="{{ route('admin.talent-shows.create') }}" class="w-full sm:w-auto btn-touch bg-indigo-600 text-white text-center">Νέο</a>
    </div>

    @if ($flashSuccess)
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-xl text-sm sm:text-base" role="status">{{ $flashSuccess }}</div>
    @endif
    @if ($flashError)
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-xl text-sm sm:text-base" role="alert">{{ $flashError }}</div>
    @endif

    <div class="admin-cards-mobile">
        @foreach ($shows as $show)
            <article class="card">
                <h2 class="font-bold text-lg">
                    <a href="{{ route('admin.talent-shows.show', $show) }}" class="text-indigo-600 hover:underline">{{ $show->title }}</a>
                </h2>
                <p class="text-sm text-gray-500 mt-1">{{ $show->status->label() }}</p>
                <p class="text-sm text-gray-500">{{ $show->event_date?->format('d/m/Y') ?? '—' }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('admin.talent-shows.edit', $show) }}" class="btn-touch-sm border border-gray-200">Επεξεργασία</a>
                    <button type="button" wire:click="askDelete({{ $show->id }})" class="btn-touch-sm border border-red-200 text-red-600">Διαγραφή</button>
                </div>
            </article>
        @endforeach
    </div>

    <div class="hidden md:block card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="admin-table-desktop">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-4">Τίτλος</th>
                        <th class="p-4">Κατάσταση</th>
                        <th class="p-4">Ημερομηνία</th>
                        <th class="p-4">Ενέργειες</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shows as $show)
                        <tr class="border-t">
                            <td class="p-4"><a href="{{ route('admin.talent-shows.show', $show) }}" class="text-indigo-600 hover:underline">{{ $show->title }}</a></td>
                            <td class="p-4">{{ $show->status->label() }}</td>
                            <td class="p-4">{{ $show->event_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.talent-shows.edit', $show) }}" class="btn-touch-sm border border-gray-200">Επεξεργασία</a>
                                    <button type="button" wire:click="askDelete({{ $show->id }})" class="btn-touch-sm border border-red-200 text-red-600">Διαγραφή</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $shows->links() }}</div>

    @if ($confirmDeleteShow)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="delete-show-index-title">
            <div class="modal-panel">
                <h3 id="delete-show-index-title" class="font-bold text-lg mb-3">Διαγραφή Talent Show;</h3>
                <p class="text-sm text-gray-600 mb-5">
                    Θα διαγραφεί οριστικά το <strong>{{ $confirmDeleteShow->title }}</strong>.
                </p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" wire:click="confirmDelete" class="w-full btn-touch bg-red-600 text-white">Ναι, διαγραφή</button>
                    <button type="button" wire:click="cancelDelete" class="w-full btn-touch border border-gray-300">Όχι</button>
                </div>
            </div>
        </div>
    @endif
</div>
