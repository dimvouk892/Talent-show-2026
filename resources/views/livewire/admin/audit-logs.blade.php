<div>
    @include('partials.admin-show-nav', ['talentShow' => $talentShow])

    @if ($flashSuccess)
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-xl text-sm sm:text-base" role="status">{{ $flashSuccess }}</div>
    @endif

    <h1 class="text-xl sm:text-2xl font-bold mb-5 sm:mb-6">Ιστορικό αλλαγών</h1>

    <div class="admin-cards-mobile">
        @forelse ($logs as $log)
            <article class="card text-sm">
                <p class="text-xs text-gray-500">{{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                <p class="font-semibold mt-1">{{ $log->action }}</p>
                <p class="text-gray-600">{{ $log->entity_type }} #{{ $log->entity_id }}</p>
                @if ($log->old_values || $log->new_values)
                    <p class="text-xs mt-2 break-all">
                        @if ($log->old_values)<span class="text-red-600">{{ json_encode($log->old_values) }}</span> → @endif
                        @if ($log->new_values)<span class="text-green-600">{{ json_encode($log->new_values) }}</span>@endif
                    </p>
                @endif
            </article>
        @empty
            <p class="text-gray-500 text-center py-8">Δεν υπάρχουν καταγραφές</p>
        @endforelse
    </div>

    <div class="hidden md:block card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="admin-table-desktop text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-3 text-left">Ημερομηνία</th>
                        <th class="p-3 text-left">Ενέργεια</th>
                        <th class="p-3 text-left">Οντότητα</th>
                        <th class="p-3 text-left">Λεπτομέρειες</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-t">
                            <td class="p-3 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="p-3 font-medium">{{ $log->action }}</td>
                            <td class="p-3">{{ $log->entity_type }} #{{ $log->entity_id }}</td>
                            <td class="p-3 text-xs max-w-xs break-all">
                                @if ($log->old_values) <span class="text-red-600">{{ json_encode($log->old_values) }}</span> → @endif
                                @if ($log->new_values) <span class="text-green-600">{{ json_encode($log->new_values) }}</span> @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-6 text-center text-gray-500">Δεν υπάρχουν καταγραφές</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>

    <section class="mt-8 border-t border-gray-200 pt-6" aria-label="Διαγραφή ιστορικού">
        <button type="button"
                wire:click="confirmClearHistory"
                class="w-full btn-touch border border-red-300 text-red-700 hover:bg-red-50">
            Διαγραφή όλου του ιστορικού
        </button>
    </section>

    @if ($showClearConfirm)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="clear-history-title">
            <div class="modal-panel">
                <h3 id="clear-history-title" class="font-bold text-lg mb-3">Διαγραφή ιστορικού;</h3>
                <p class="text-sm sm:text-base text-gray-600 mb-4 leading-relaxed">
                    Θα διαγραφούν όλες οι καταγραφές αλλαγών για αυτή την εκδήλωση (ενέργειες admin, κριτών, διορθώσεις βαθμών).
                </p>
                <p class="text-sm font-medium text-red-700 mb-5">Η ενέργεια δεν αναιρείται.</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" wire:click="clearHistory" class="w-full btn-touch bg-red-600 text-white hover:bg-red-500">Ναι, διαγραφή</button>
                    <button type="button" wire:click="cancelClearHistory" class="w-full btn-touch border border-gray-300">Όχι, ακύρωση</button>
                </div>
            </div>
        </div>
    @endif
</div>
