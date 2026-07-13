<div wire:poll.2s="pollLiveState" class="w-full max-w-4xl mx-auto">
    @include('partials.admin-show-nav', ['talentShow' => $talentShow])

    @if ($flashSuccess)
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-xl text-sm sm:text-base" role="status">{{ $flashSuccess }}</div>
    @endif
    @if ($flashError)
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-xl text-sm sm:text-base" role="alert">{{ $flashError }}</div>
    @endif

    <header class="mb-5 sm:mb-6">
        <h1 class="text-xl sm:text-2xl font-bold">Ζωντανός Έλεγχος</h1>
        <p class="text-gray-500 text-sm sm:text-base mt-1">Κατάσταση: {{ $talentShow->status->label() }}</p>
        <p class="mt-3 text-sm text-indigo-800 bg-indigo-50 rounded-xl px-4 py-3">{{ $flowHint }}</p>
        <a href="{{ route('admin.talent-shows.screen-videos', $talentShow) }}" class="inline-block mt-2 text-sm text-indigo-600 underline">
            Videos στην οθόνη →
        </a>
    </header>

    <section class="card mb-5 sm:mb-6 space-y-4" aria-label="Ροή εκδήλωσης">
        <h2 class="font-semibold text-base">Ροή εκδήλωσης</h2>

        <div class="space-y-3">
            <div class="p-4 rounded-xl border {{ $canStartShow || $canOpenScoring ? 'border-indigo-200 bg-indigo-50/50' : 'border-gray-100 bg-gray-50/50' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">1. Έναρξη</p>
                <div class="flex flex-col sm:flex-row gap-2">
                    <button type="button"
                            wire:click="startShow"
                            @disabled(! $canStartShow)
                            class="w-full sm:flex-1 btn-touch bg-gray-800 text-white hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        Έναρξη Talent Show
                    </button>
                    <button type="button"
                            wire:click="openScoring"
                            @disabled(! $canOpenScoring)
                            class="w-full sm:flex-1 btn-touch bg-green-600 text-white hover:bg-green-500 disabled:opacity-40 disabled:cursor-not-allowed">
                        Έναρξη βαθμολόγησης
                    </button>
                </div>
            </div>

            <div class="p-4 rounded-xl border {{ $talentShow->status->value === 'scoring_open' ? 'border-indigo-200 bg-indigo-50/50' : 'border-gray-100 bg-gray-50/50' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">2. Διαγωνισμός</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button type="button"
                            wire:click="revealScores"
                            @disabled(! $canRevealScores)
                            class="w-full btn-touch bg-blue-600 text-white hover:bg-blue-500 disabled:opacity-40 disabled:cursor-not-allowed">
                        Εμφάνιση σκορ
                    </button>
                    <button type="button"
                            wire:click="hideScores"
                            @disabled(! $canHideScores)
                            class="w-full btn-touch bg-gray-600 text-white hover:bg-gray-500 disabled:opacity-40 disabled:cursor-not-allowed">
                        Απόκρυψη σκορ
                    </button>
                    <button type="button"
                            wire:click="confirmNext"
                            @disabled(! $canProceed)
                            class="w-full btn-touch bg-indigo-600 text-white hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed sm:col-span-2">
                        Επόμενος διαγωνιζόμενος
                    </button>
                    <button type="button"
                            wire:click="closeScoring"
                            @disabled(! $canCloseScoring)
                            class="w-full btn-touch bg-orange-600 text-white hover:bg-orange-500 disabled:opacity-40 disabled:cursor-not-allowed sm:col-span-2">
                        Κλείσιμο βαθμολόγησης
                    </button>
                </div>
            </div>

            <div class="p-4 rounded-xl border {{ in_array($talentShow->status->value, ['scoring_closed', 'results_ready', 'winner_revealed']) ? 'border-indigo-200 bg-indigo-50/50' : 'border-gray-100 bg-gray-50/50' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">3. Αποτελέσματα &amp; τέλος</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button type="button"
                            wire:click="showRanking"
                            @disabled(! $canShowRanking)
                            class="w-full btn-touch bg-purple-600 text-white hover:bg-purple-500 disabled:opacity-40 disabled:cursor-not-allowed">
                        Εμφάνιση κατάταξης
                    </button>
                    <button type="button"
                            wire:click="revealWinner"
                            @disabled(! $canRevealWinner)
                            class="w-full btn-touch bg-yellow-600 text-white hover:bg-yellow-500 disabled:opacity-40 disabled:cursor-not-allowed">
                        Αποκάλυψη νικητή
                    </button>
                    <button type="button"
                            wire:click="completeShow"
                            @disabled(! $canCompleteShow)
                            class="w-full btn-touch bg-teal-600 text-white hover:bg-teal-500 disabled:opacity-40 disabled:cursor-not-allowed sm:col-span-2">
                        Ολοκλήρωση Talent Show
                    </button>
                </div>
            </div>
        </div>
    </section>

    @if ($currentTeam)
        <section class="card mb-5 sm:mb-6" aria-label="Τρέχουσα ομάδα">
            <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 items-center sm:items-start text-center sm:text-left">
                @if ($currentTeam->photo_path)
                    <img src="{{ $currentTeam->photoUrl() }}"
                         alt="{{ $currentTeam->name }}"
                         class="w-28 h-28 sm:w-32 sm:h-32 rounded-xl object-cover shrink-0">
                @endif
                <div class="flex-1 min-w-0 w-full">
                    <p class="text-sm text-gray-500">Τρέχουσα ομάδα</p>
                    <h2 class="text-2xl sm:text-3xl font-bold break-words">{{ $currentTeam->name }}</h2>
                    <p class="text-gray-500 text-sm mt-1">Σειρά {{ $currentTeam->display_order }} · {{ $currentTeam->status->label() }}</p>
                    @if ($talentShow->showing_team_intro && $currentTeam->video_path)
                        <p class="mt-2 text-sm font-medium text-amber-700 bg-amber-50 inline-block px-3 py-1 rounded-lg">Προβολή intro video στην οθόνη</p>
                    @endif
                </div>
            </div>

            @if ($talentShow->showing_team_intro && $currentTeam->video_path)
                <button type="button" wire:click="dismissTeamIntro" class="mt-4 w-full sm:w-auto btn-touch bg-amber-600 text-white hover:bg-amber-500">
                    Έναρξη παρουσίασης (παράλειψη intro)
                </button>
            @endif

            @if ($scores)
                <div class="mt-4 p-4 bg-indigo-50 rounded-xl text-center sm:text-left">
                    <p class="text-lg font-semibold text-indigo-900">
                        Ψήφισαν {{ $scores['votes_count'] }} / {{ $scores['active_judges_count'] }}
                    </p>
                    @if ($scores['votes_count'] > 0)
                        <p class="font-bold mt-1 text-indigo-800">
                            @if ($scores['is_complete'])
                                Σύνολο: {{ $scores['total_score'] }} / {{ $scores['maximum_score'] }}
                            @else
                                Προσωρινό σύνολο: {{ $scores['total_score'] }}
                                <span class="block text-sm font-normal text-indigo-700 mt-1">Αναμονή {{ $scores['active_judges_count'] - $scores['votes_count'] }} κριτών</span>
                            @endif
                        </p>
                        <p class="text-indigo-700">
                            Μέσος όρος: {{ number_format($scores['average_score'], 2, ',', '') }} / 10
                            @if (! $scores['is_complete'])
                                <span class="text-sm">(μέχρι στιγμής)</span>
                            @endif
                        </p>
                    @endif
                </div>
            @endif

            <p class="mt-4 text-sm text-gray-500">
                @if ($talentShow->showing_team_intro)
                    Περιμένετε το τέλος του intro πριν την ψηφοφορία.
                @elseif ($canProceed)
                    Όλοι οι κριτές ψήφισαν — μπορείτε να προχωρήσετε στον επόμενο διαγωνιζόμενο.
                @else
                    Περιμένετε όλες τις ψήφους πριν την επόμενη ομάδα.
                @endif
            </p>

            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3" role="list" aria-label="Κατάσταση κριτών">
                @foreach ($judgeStatus as $item)
                    <div class="flex justify-between items-center gap-3 p-3 sm:p-4 bg-gray-50 rounded-xl min-h-12" role="listitem">
                        <span class="text-sm sm:text-base min-w-0">
                            <span class="font-medium">{{ $item['judge']->name }}</span>
                            @if ($item['judge']->title)
                                <span class="block text-xs text-gray-500 truncate">{{ $item['judge']->title }}</span>
                            @endif
                        </span>
                        @if ($item['has_voted'])
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xl font-bold text-indigo-600">{{ $item['score'] }}</span>
                                <button type="button"
                                        wire:click="openCorrection({{ $item['vote']->id }})"
                                        class="btn-touch-sm text-xs border border-gray-200 text-gray-600">
                                    Διόρθωση
                                </button>
                            </div>
                        @else
                            <span class="text-gray-400 text-sm shrink-0">Αναμονή</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @else
        <div class="card mb-5 sm:mb-6 text-center text-gray-500 py-8">
            Δεν υπάρχει ενεργή ομάδα
        </div>
    @endif

    <section class="mt-8 border-t border-gray-200 pt-6" x-data="{ dangerOpen: false }" aria-label="Επικίνδυνες ενέργειες">
        <button type="button"
                @click="dangerOpen = !dangerOpen"
                :aria-expanded="dangerOpen"
                class="w-full btn-touch border border-gray-300 text-gray-800 hover:bg-gray-50 justify-between">
            <span>Επικίνδυνες ενέργειες</span>
            <svg class="w-5 h-5 transition-transform shrink-0" :class="dangerOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="dangerOpen"
             x-cloak
             x-transition
             class="mt-3 card border-red-100 bg-red-50/40 space-y-3">
            <div>
                <h2 class="font-semibold text-red-900">Καθαρισμός βαθμολογιών</h2>
                <p class="text-sm text-red-800/80 mt-1">
                    Διαγραφή όλων των ψήφων και διορθώσεων από τη βάση. Η εκδήλωση επανέρχεται σε «Έτοιμο» χωρίς αυτόματη έναρξη.
                </p>
            </div>
            <button type="button"
                    wire:click="askClearScores"
                    class="w-full btn-touch bg-red-600 text-white hover:bg-red-500">
                Καθαρισμός σκορ
            </button>

            <div class="border-t border-red-100 pt-3">
                <h2 class="font-semibold text-red-900">Επανεκκίνηση εκδήλωσης</h2>
                <p class="text-sm text-red-800/80 mt-1">
                    Διαγραφή βαθμολογιών και άμεση έναρξη βαθμολόγησης από την 1η ομάδα.
                </p>
            </div>
            <button type="button"
                    wire:click="confirmRestart"
                    class="w-full btn-touch border border-red-300 text-red-700 hover:bg-red-50">
                Διαγραφή δεδομένων &amp; ξανά έναρξη
            </button>
            <button type="button"
                    wire:click="askRevokeAllSessions"
                    class="w-full btn-touch border border-orange-300 text-orange-700 hover:bg-orange-50">
                Αποσύνδεση όλων των κριτών
            </button>
            <button type="button"
                    wire:click="askArchive"
                    class="w-full btn-touch border border-gray-300 text-gray-700 hover:bg-gray-50">
                Αρχειοθέτηση εκδήλωσης
            </button>
        </div>
    </section>

    @if ($showClearScoresConfirm)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="clear-scores-title">
            <div class="modal-panel">
                <h3 id="clear-scores-title" class="font-bold text-lg mb-3">Καθαρισμός βαθμολογιών;</h3>
                <p class="text-sm sm:text-base text-gray-600 mb-4 leading-relaxed">
                    Θα διαγραφούν όλες οι ψήφοι και οι διορθώσεις από τη βάση.
                    Οι ομάδες επανέρχονται σε αναμονή και η εκδήλωση σε «Έτοιμο».
                    Οι κριτές παραμένουν συνδεδεμένοι — μπορούν να ψηφίσουν ξανά μετά την «Έναρξη βαθμολόγησης».
                </p>
                <p class="text-sm font-medium text-red-700 mb-5">Η ενέργεια δεν αναιρείται.</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" wire:click="confirmClearScores" class="w-full btn-touch bg-red-600 text-white hover:bg-red-500">Ναι, καθαρισμός</button>
                    <button type="button" wire:click="cancelDangerConfirm" class="w-full btn-touch border border-gray-300">Όχι, ακύρωση</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showRestartConfirm)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="restart-show-title">
            <div class="modal-panel">
                <h3 id="restart-show-title" class="font-bold text-lg mb-3">Διαγραφή δεδομένων &amp; ξανά έναρξη;</h3>
                <p class="text-sm sm:text-base text-gray-600 mb-4 leading-relaxed">
                    Θα διαγραφούν όλες οι βαθμολογίες και οι διορθώσεις.
                    Οι ομάδες θα επανέλθουν σε κατάσταση αναμονής και θα ενεργοποιηθεί αμέσως η 1η ομάδα.
                    Οι κριτές παραμένουν συνδεδεμένοι.
                </p>
                <p class="text-sm font-medium text-red-700 mb-5">Η ενέργεια δεν αναιρείται.</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" wire:click="restartShow" class="w-full btn-touch bg-red-600 text-white hover:bg-red-500">Ναι, επανεκκίνηση</button>
                    <button type="button" wire:click="cancelDangerConfirm" class="w-full btn-touch border border-gray-300">Όχι, ακύρωση</button>
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
                    <button type="button" wire:click="confirmRevokeAllJudgeSessions" class="w-full btn-touch bg-orange-600 text-white">Ναι, αποσύνδεση</button>
                    <button type="button" wire:click="cancelDangerConfirm" class="w-full btn-touch border border-gray-300">Όχι</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showArchiveConfirm)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="archive-title">
            <div class="modal-panel">
                <h3 id="archive-title" class="font-bold text-lg mb-3">Αρχειοθέτηση εκδήλωσης;</h3>
                <p class="text-sm text-gray-600 mb-5">Η εκδήλωση θα αρχειοθετηθεί και όλοι οι κριτές θα αποσυνδεθούν.</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" wire:click="archiveShow" class="w-full btn-touch bg-gray-800 text-white">Ναι, αρχειοθέτηση</button>
                    <button type="button" wire:click="cancelDangerConfirm" class="w-full btn-touch border border-gray-300">Όχι</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showNextConfirm)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="next-team-title">
            <div class="modal-panel">
                <p id="next-team-title" class="text-base sm:text-lg mb-5">Επιβεβαίωση μετάβασης στον επόμενο διαγωνιζόμενο;</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" wire:click="nextTeam" class="w-full btn-touch bg-indigo-600 text-white">Ναι</button>
                    <button type="button" wire:click="$set('showNextConfirm', false)" class="w-full btn-touch border border-gray-300">Όχι</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showCorrectionForm && $correctingVoteId)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="correction-title">
            <div class="modal-panel">
                <h3 id="correction-title" class="font-bold text-lg mb-4">Διόρθωση βαθμού</h3>
                <div class="space-y-4">
                    <div>
                        <label for="correction-score" class="block text-sm font-medium mb-1">Νέος βαθμός (1-10)</label>
                        <input id="correction-score" type="number" wire:model="correctionScore" min="1" max="10" class="input-touch">
                    </div>
                    <div>
                        <label for="correction-reason" class="block text-sm font-medium mb-1">Αιτιολογία *</label>
                        <textarea id="correction-reason" wire:model="correctionReason" class="input-touch min-h-[100px]" rows="3" required></textarea>
                        @error('correctionReason') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="button" wire:click="correctVote" class="w-full btn-touch bg-indigo-600 text-white">Αποθήκευση</button>
                        <button type="button" wire:click="$set('showCorrectionForm', false)" class="w-full btn-touch border border-gray-300">Ακύρωση</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showWinnerSelect && count($tiedTeams) > 1)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="tie-title">
            <div class="modal-panel">
                <h3 id="tie-title" class="font-bold text-lg mb-4">Ισοβαθμία — Επιλέξτε νικητή</h3>
                <label for="winner-select" class="sr-only">Επιλογή νικητή</label>
                <select id="winner-select" wire:model="selectedWinnerId" class="input-touch mb-4">
                    <option value="">— Επιλέξτε —</option>
                    @foreach ($tiedTeams as $item)
                        <option value="{{ $item['team']->id }}">{{ $item['team']->name }} ({{ $item['total_score'] }} πόντοι)</option>
                    @endforeach
                </select>
                <button type="button" wire:click="revealWinner" class="w-full btn-touch bg-yellow-600 text-white">Αποκάλυψη</button>
            </div>
        </div>
    @endif
</div>
