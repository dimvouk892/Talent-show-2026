<div>
    @include('partials.admin-show-nav', ['talentShow' => $talentShow])

    @if ($flashSuccess)
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-xl text-sm sm:text-base" role="status">{{ $flashSuccess }}</div>
    @endif
    @if ($flashError)
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-xl text-sm sm:text-base" role="alert">{{ $flashError }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
        <h1 class="text-lg sm:text-xl font-bold">Κριτές ({{ $judges->count() }})</h1>
        <button type="button" wire:click="openCreate" class="w-full sm:w-auto btn-touch bg-indigo-600 text-white hover:bg-indigo-500">Νέος κριτής</button>
    </div>

    <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-xl text-sm text-yellow-800" role="note">
        Κάθε QR είναι <strong>προσωπικό</strong> — ένας κριτής, ένα QR. Δημιουργήστε QR για όλους πριν την εκδήλωση.
    </div>

    @if ($showForm)
        <form wire:submit="save" class="card mb-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="judge-name" class="block text-sm font-medium mb-1">Όνομα *</label>
                    <input id="judge-name" type="text" wire:model.blur="name" class="input-touch" required>
                </div>
                <div>
                    <label for="judge-title" class="block text-sm font-medium mb-1">Τίτλος</label>
                    <input id="judge-title" type="text" wire:model.blur="title" class="input-touch">
                </div>
                <label class="flex items-center gap-3 min-h-12">
                    <input type="checkbox" wire:model="is_active" class="w-5 h-5 rounded">
                    <span>Ενεργός</span>
                </label>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <button type="submit" class="w-full sm:w-auto btn-touch bg-indigo-600 text-white">Αποθήκευση</button>
                <button type="button" wire:click="resetForm" class="w-full sm:w-auto btn-touch border border-gray-300">Ακύρωση</button>
            </div>
        </form>
    @endif

    <label class="flex items-start gap-3 mb-4 text-sm min-h-11">
        <input type="checkbox" wire:model.live="revokePreviousSessions" class="w-5 h-5 mt-0.5 rounded shrink-0">
        <span>Ανάκληση προηγούμενων sessions κατά τη δημιουργία QR</span>
    </label>

    <div class="mb-4 flex flex-col sm:flex-row gap-2">
        <button type="button"
                wire:click="generateAllQrs"
                class="w-full sm:w-auto btn-touch bg-indigo-600 text-white hover:bg-indigo-500">
            Δημιουργία QR για όλους
        </button>
        <button type="button"
                wire:click="askRevokeAllSessions"
                class="w-full sm:w-auto btn-touch border border-orange-300 text-orange-700 hover:bg-orange-50">
            Αποσύνδεση όλων των κριτών
        </button>
    </div>

    <div class="space-y-4">
        @foreach ($judges as $judge)
            @php $status = $judgeStatus[$judge->id]; @endphp
            <article class="card">
                <div class="space-y-4">
                    <div>
                        <h3 class="font-bold text-lg">
                            {{ $judge->name }}
                        </h3>
                        @if ($judge->title)<p class="text-sm text-gray-500">{{ $judge->title }}</p>@endif
                        <div class="flex flex-wrap gap-2 mt-2 text-sm">
                            <span class="px-2 py-1 rounded-lg {{ $judge->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $judge->is_active ? 'Ενεργός' : 'Ανενεργός' }}
                            </span>
                            <span class="px-2 py-1 rounded-lg {{ $status['has_active_session'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ $status['has_active_session'] ? 'Συνδεδεμένος' : 'Αποσυνδεδεμένος' }}
                            </span>
                            <span class="px-2 py-1 rounded-lg bg-indigo-50 text-indigo-700">
                                QR: {{ $status['has_valid_qr'] ? 'Ενεργό' : '—' }}
                            </span>
                        </div>
                        @if ($status['last_access'])
                            <p class="text-xs text-gray-400 mt-2">Τελευταία πρόσβαση: {{ $status['last_access']->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>

                    @if (isset($qrPreviews[$judge->id]))
                        <div class="pt-4 border-t border-indigo-100 bg-indigo-50/40 rounded-xl p-4 text-center" role="region" aria-label="Προσωπικό QR Code {{ $judge->name }}" wire:key="qr-preview-{{ $judge->id }}">
                            <p class="text-sm font-semibold text-indigo-900">Προσωπικό QR — {{ $judge->name }}</p>
                            @if ($judge->title)
                                <p class="text-xs text-indigo-700/80 mt-1">{{ $judge->title }}</p>
                            @endif
                            <p class="text-xs text-gray-500 mt-2 mb-3">Μόνο για αυτόν τον κριτή · Σαρώστε μία φορά και συνεχίστε χωρίς νέο scan</p>
                            <div class="inline-block p-1.5 bg-white rounded-lg border border-gray-100 [&_svg]:w-[min(140px,36vw)] [&_svg]:h-auto [&_svg]:mx-auto [&_svg]:block">
                                {!! $qrPreviews[$judge->id] !!}
                            </div>
                            <a href="{{ $qrUrls[$judge->id] }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="inline-block text-xs text-indigo-600 hover:text-indigo-800 mt-2 break-all px-2 underline max-w-full">
                                {{ $qrUrls[$judge->id] }}
                            </a>
                            <div class="mt-3 flex flex-col sm:flex-row gap-2 justify-center">
                                <a href="{{ route('admin.judges.qr.download', $judge) }}" class="w-full sm:w-auto btn-touch-sm bg-indigo-600 text-white">Λήψη PNG</a>
                                <a href="{{ route('admin.judges.qr.print', $judge) }}" target="_blank" rel="noopener" class="w-full sm:w-auto btn-touch-sm bg-gray-700 text-white">Εκτύπωση</a>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <button type="button" wire:click="edit({{ $judge->id }})" class="w-full btn-touch-sm border border-gray-200">Επεξεργασία</button>
                        <button type="button" wire:click="generateQr({{ $judge->id }})" class="w-full btn-touch-sm bg-indigo-100 text-indigo-700">Δημιουργία QR</button>
                        <button type="button" wire:click="askRevokeQr({{ $judge->id }})" class="w-full btn-touch-sm border border-orange-200 text-orange-700">Ακύρωση QR</button>
                        <button type="button" wire:click="askRevokeSession({{ $judge->id }})" class="w-full btn-touch-sm border border-orange-200 text-orange-700">Αποσύνδεση κριτή</button>
                        <button type="button" wire:click="askDelete({{ $judge->id }})" class="w-full sm:col-span-2 btn-touch-sm border border-red-200 text-red-600">Διαγραφή</button>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    @if ($confirmRevokeQrId)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="revoke-qr-title">
            <div class="modal-panel">
                <h3 id="revoke-qr-title" class="font-bold text-lg mb-3">Ακύρωση QR;</h3>
                <p class="text-sm text-gray-600 mb-5">Το παλιό token θα σταματήσει να λειτουργεί. Ο κριτής θα χρειαστεί νέο QR scan.</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" wire:click="confirmRevokeQr" class="w-full btn-touch bg-orange-600 text-white">Ναι, ακύρωση</button>
                    <button type="button" wire:click="cancelConfirm" class="w-full btn-touch border border-gray-300">Όχι</button>
                </div>
            </div>
        </div>
    @endif

    @if ($confirmRevokeSessionId)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="revoke-session-title">
            <div class="modal-panel">
                <h3 id="revoke-session-title" class="font-bold text-lg mb-3">Αποσύνδεση κριτή;</h3>
                <p class="text-sm text-gray-600 mb-5">Η ενεργή σύνδεση του κριτή θα τερματιστεί. Θα χρειαστεί νέο QR scan.</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" wire:click="confirmRevokeSession" class="w-full btn-touch bg-orange-600 text-white">Ναι, αποσύνδεση</button>
                    <button type="button" wire:click="cancelConfirm" class="w-full btn-touch border border-gray-300">Όχι</button>
                </div>
            </div>
        </div>
    @endif

    @if ($confirmDeleteId)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="delete-judge-title">
            <div class="modal-panel">
                <h3 id="delete-judge-title" class="font-bold text-lg mb-3">Διαγραφή κριτή;</h3>
                <p class="text-sm text-gray-600 mb-5">Η ενέργεια δεν αναιρείται.</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" wire:click="confirmDelete" class="w-full btn-touch bg-red-600 text-white">Ναι, διαγραφή</button>
                    <button type="button" wire:click="cancelConfirm" class="w-full btn-touch border border-gray-300">Όχι</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showRevokeAllConfirm)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="revoke-all-title">
            <div class="modal-panel">
                <h3 id="revoke-all-title" class="font-bold text-lg mb-3">Αποσύνδεση όλων των κριτών;</h3>
                <p class="text-sm text-gray-600 mb-5">Όλοι οι κριτές θα αποσυνδεθούν και θα χρειαστούν νέο QR scan.</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" wire:click="confirmRevokeAllSessions" class="w-full btn-touch bg-orange-600 text-white">Ναι, αποσύνδεση όλων</button>
                    <button type="button" wire:click="cancelConfirm" class="w-full btn-touch border border-gray-300">Όχι</button>
                </div>
            </div>
        </div>
    @endif
</div>
