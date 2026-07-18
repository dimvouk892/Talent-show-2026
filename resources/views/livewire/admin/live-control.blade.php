<div wire:poll.2s="pollLiveState" class="w-full max-w-6xl mx-auto">
    @include('partials.admin-show-nav', ['talentShow' => $talentShow])

    @if ($flashSuccess)
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-xl text-sm" role="status">{{ $flashSuccess }}</div>
    @endif
    @if ($flashError)
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-xl text-sm" role="alert">{{ $flashError }}</div>
    @endif

    <header class="mb-5">
        <h1 class="text-xl sm:text-2xl font-bold">Ζωντανός Έλεγχος</h1>
        <p class="text-gray-500 text-sm mt-1">Κατάσταση: {{ $talentShow->status->label() }}</p>
        <p class="mt-3 text-sm text-indigo-800 bg-indigo-50 rounded-xl px-4 py-3">{{ $flowHint }}</p>
    </header>

    @php
        $votingFinished = in_array($talentShow->status->value, ['scoring_closed', 'results_ready', 'winner_revealed'], true)
            && ! $hasPendingFinalVote;
        $ceremonyActive = $podium['step'] > 0 && ! $podium['is_complete'];
        $podiumComplete = $podium['is_complete'] || $talentShow->winner_revealed;
        $showPodiumControls = $canStartPodiumReveal || $canAdvancePodium || $canRewindPodium || $ceremonyActive;
        $showEndScreenControls = $canShowFinalOverview || $canHideFinalOverview
            || $canShowFinalChart || $canHideFinalChart
            || $canShowScoreboardPanel || $canHideScoreboardPanel;
        $showPresentationControls = $showPodiumControls || $showEndScreenControls;
    @endphp

    {{-- Έναρξη ψηφοφορίας (μόνο από αναμονή / Ready) --}}
    @if ($canOpenScoring)
    <section class="card mb-5 space-y-3" aria-label="Έναρξη ψηφοφορίας">
        <button type="button"
                wire:click="openScoring"
                class="w-full btn-touch text-lg bg-green-600 text-white hover:bg-green-500">
            Έναρξη ψηφοφορίας
        </button>
        <p class="text-sm text-gray-500 text-center">
            Ξεκινά η βαθμολόγηση από την 1η ομάδα.
        </p>
    </section>
    @endif

    {{-- Κύρια κουμπιά ροής ψηφοφορίας --}}
    @if (! $votingFinished)
    <section class="card mb-5 space-y-3" aria-label="Κύριες ενέργειες">
        @if ($talentShow->status->value === 'scoring_open')
            @if ($canRevealScores)
                <button type="button"
                        wire:click="revealScores"
                        class="w-full btn-touch text-lg bg-teal-600 text-white hover:bg-teal-500">
                    Εμφάνιση σκορ στο monitor
                </button>
                <p class="text-sm text-gray-500 text-center">Τα σκορ δεν εμφανίζονται μέχρι να πατήσετε αυτό το κουμπί.</p>
            @elseif ($canHideScores)
                <button type="button"
                        wire:click="hideScores"
                        class="w-full btn-touch border border-teal-300 text-teal-800 hover:bg-teal-50">
                    Απόκρυψη σκορ από monitor
                </button>
            @endif

            <button type="button"
                    wire:click="nextTeam"
                    @disabled(! $canProceed)
                    class="w-full btn-touch text-lg bg-indigo-600 text-white hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed">
                Επόμενη ομάδα
            </button>
            @if ($canProceed)
                <p class="text-sm text-center font-medium text-green-700">Όλοι οι κριτές ψήφισαν — μπορείτε να προχωρήσετε.</p>
            @else
                <p class="text-sm text-center text-amber-700">Περιμένετε να ψηφίσουν όλοι οι κριτές.</p>
            @endif
        @endif

        @if ($hasPendingFinalVote && $talentShow->final_vote_open)
            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-900 text-center">
                Αναμονή τελικής ψήφου από <strong>{{ $finalVoter?->name ?? 'ειδικό κριτή' }}</strong>.
            </div>
        @elseif ($canOpenFinalVote)
            <button type="button" wire:click="openFinalVote" class="w-full btn-touch bg-amber-600 text-white hover:bg-amber-500">
                Άνοιγμα τελικής ψήφου
            </button>
        @endif

        @if ($finalVoter && $hasPendingFinalVote && in_array($talentShow->status->value, ['scoring_closed', 'results_ready', 'winner_revealed'], true))
            <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/60 space-y-3">
                <p class="text-sm font-semibold text-amber-900">Τελική ψήφος — {{ $finalVoter->name }}</p>
                <p class="text-sm text-amber-800">Δεν έχει υποβληθεί ακόμη.</p>
                <button type="button" wire:click="openFinalVoteForm" class="w-full btn-touch bg-amber-600 text-white hover:bg-amber-500">
                    Καταχώρηση τελικής ψήφου
                </button>
            </div>
        @endif
    </section>
    @endif

    @if ($showPresentationControls)
        @if ($showPodiumControls && ! $podiumComplete)
            <section class="card mb-5 space-y-3" aria-label="Αποκάλυψη top 5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <h2 class="text-lg font-bold">Αποκάλυψη 5 → 1</h2>
                    <a href="{{ route('presentation.show') }}" target="_blank" rel="noopener"
                       class="btn-touch-sm text-center bg-gray-800 text-white hover:bg-gray-700">
                        Monitor ↗
                    </a>
                </div>

                @if ($canStartPodiumReveal)
                    <button type="button" wire:click="startPodiumReveal" class="w-full btn-touch text-lg bg-yellow-600 text-white hover:bg-yellow-500">
                        Αποκάλυψη 5ης θέσης
                    </button>
                    <p class="text-sm text-gray-500 text-center">
                        Ξεκινά η τελετή: 5η → 4η → 3η → 2η → 1η (νικητής).
                    </p>
                @endif

                @if ($ceremonyActive || $canRewindPodium)
                    <p class="text-sm text-center text-gray-600 font-medium">
                        Βήμα {{ $podium['step'] }} / {{ $podium['total_steps'] }}
                        @if ($podium['current'])
                            — Τώρα: {{ $podium['current']['ranking_position'] }}η θέση
                        @endif
                        @if ($podium['next'])
                            — Επόμενο: {{ $podium['next']['ranking_position'] }}η
                        @endif
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button type="button"
                                wire:click="previousPodiumReveal"
                                @disabled(! $canRewindPodium)
                                class="w-full btn-touch border border-gray-300 text-gray-800 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                            Προηγούμενο
                        </button>
                        <button type="button"
                                wire:click="nextPodiumReveal"
                                @disabled(! $canAdvancePodium)
                                class="w-full btn-touch text-lg bg-yellow-600 text-white hover:bg-yellow-500 disabled:opacity-40 disabled:cursor-not-allowed">
                            @if ($podium['next'])
                                Αποκάλυψη {{ $podium['next']['ranking_position'] }}ης θέσης
                            @else
                                Αποκάλυψη νικητή
                            @endif
                        </button>
                    </div>
                @endif
            </section>
        @endif

        @if ($showEndScreenControls)
            <section class="card mb-5 space-y-3" aria-label="Οθόνες αποτελεσμάτων">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <h2 class="text-lg font-bold">Οθόνες αποτελεσμάτων</h2>
                    <a href="{{ route('presentation.show') }}" target="_blank" rel="noopener"
                       class="btn-touch-sm text-center bg-gray-800 text-white hover:bg-gray-700">
                        Monitor ↗
                    </a>
                </div>
                <p class="text-sm text-gray-500 text-center">Επιλέξτε τι εμφανίζεται στο monitor.</p>

                @if ($canShowFinalOverview)
                    <button type="button" wire:click="showFinalOverview" class="w-full btn-touch bg-indigo-600 text-white hover:bg-indigo-500">
                        Τελική κατάταξη
                    </button>
                @elseif ($canHideFinalOverview)
                    <button type="button" wire:click="hideFinalOverview" class="w-full btn-touch border border-indigo-300 text-indigo-800 hover:bg-indigo-50">
                        Απόκρυψη τελικής κατάταξης
                    </button>
                @endif

                @if ($canShowFinalChart)
                    <button type="button" wire:click="showFinalChart" class="w-full btn-touch bg-teal-600 text-white hover:bg-teal-500">
                        Γράφημα
                    </button>
                @elseif ($canHideFinalChart)
                    <button type="button" wire:click="hideFinalChart" class="w-full btn-touch border border-teal-300 text-teal-800 hover:bg-teal-50">
                        Απόκρυψη γραφήματος
                    </button>
                @endif

                @if ($canShowScoreboardPanel)
                    <button type="button" wire:click="showScoreboard" class="w-full btn-touch bg-slate-800 text-white hover:bg-slate-700">
                        Πίνακας βαθμολογιών
                    </button>
                @elseif ($canHideScoreboardPanel)
                    <button type="button" wire:click="hideScoreboard" class="w-full btn-touch border border-slate-400 text-slate-800 hover:bg-slate-50">
                        Απόκρυψη πίνακα βαθμολογιών
                    </button>
                @endif
            </section>
        @endif
    @elseif ($votingFinished)
        <section class="card mb-5 text-center space-y-2" aria-label="Ολοκλήρωση">
            <p class="text-lg font-semibold text-green-800">Η διαδικασία ολοκληρώθηκε.</p>
            <p class="text-sm text-gray-500">Για νέα εκδήλωση: Ρυθμίσεις → Διαγραφή σκορ, μετά Έναρξη.</p>
            <a href="{{ route('presentation.show') }}" target="_blank" rel="noopener"
               class="inline-flex btn-touch-sm bg-gray-800 text-white hover:bg-gray-700">
                Monitor ↗
            </a>
        </section>
    @endif

    {{-- Τρέχουσα ομάδα + κριτές --}}
    @if ($currentTeam)
        <section class="card mb-5" aria-label="Τρέχουσα ομάδα και ψήφοι">
            <div class="mb-4">
                <p class="text-sm text-gray-500 uppercase tracking-wide">Τρέχουσα ομάδα</p>
                <h2 class="text-2xl sm:text-3xl font-bold break-words mt-1">{{ $currentTeam->name }}</h2>
                <p class="text-gray-500 text-sm mt-1">Σειρά εμφάνισης: {{ $currentTeam->display_order }}</p>
            </div>

            @if ($scores)
                @php
                    $voted = $scores['votes_count'];
                    $total = $scores['active_judges_count'];
                    $pct = $total > 0 ? round(($voted / $total) * 100) : 0;
                @endphp

                <div class="mb-5 p-4 rounded-xl {{ $scores['is_complete'] ? 'bg-green-50 border border-green-200' : 'bg-indigo-50 border border-indigo-100' }}">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <p class="text-lg font-bold {{ $scores['is_complete'] ? 'text-green-800' : 'text-indigo-900' }}">
                            Ψήφισαν {{ $voted }} από {{ $total }} κριτές
                        </p>
                        @if ($scores['is_complete'])
                            <span class="text-sm font-semibold text-green-700 bg-green-100 px-2 py-1 rounded-lg">Ολοκληρώθηκε</span>
                        @endif
                    </div>
                    <div class="w-full bg-white/80 rounded-full h-3 overflow-hidden" aria-hidden="true">
                        <div class="h-3 rounded-full transition-all duration-500 {{ $scores['is_complete'] ? 'bg-green-500' : 'bg-indigo-500' }}"
                             style="width: {{ $pct }}%"></div>
                    </div>
                    @if ($scores['votes_count'] > 0)
                        <p class="mt-3 font-semibold {{ $scores['is_complete'] ? 'text-green-900' : 'text-indigo-800' }}">
                            Σύνολο: {{ $scores['total_score'] }}
                        </p>
                    @endif
                </div>
            @endif

            <h3 class="font-semibold text-gray-800 mb-3">Κριτές</h3>
            <div class="space-y-2" role="list">
                @forelse ($judgeStatus as $item)
                    <div class="flex justify-between items-center gap-3 p-3 sm:p-4 rounded-xl border
                                {{ $item['has_voted'] ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200' }}"
                         role="listitem">
                        <div class="min-w-0">
                            <p class="font-medium truncate">{{ $item['judge']->name }}</p>
                            @if ($item['judge']->title)
                                <p class="text-xs text-gray-500 truncate">{{ $item['judge']->title }}</p>
                            @endif
                        </div>
                        @if ($item['has_voted'])
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-sm text-green-700 font-medium">Ψήφισε</span>
                                <span class="text-2xl font-bold text-indigo-700 tabular-nums">{{ $item['score'] }}</span>
                                <button type="button"
                                        wire:click="openCorrection({{ $item['vote']->id }})"
                                        class="btn-touch-sm text-xs border border-gray-300 text-gray-600">
                                    Διόρθωση
                                </button>
                            </div>
                        @else
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-amber-600 text-sm font-medium">Αναμονή…</span>
                                <button type="button"
                                        wire:click="openProxyVote({{ $item['judge']->id }})"
                                        class="btn-touch-sm text-xs bg-amber-600 text-white hover:bg-amber-500">
                                    Καταχώρηση
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Δεν υπάρχουν κριτές βαθμολόγησης.</p>
                @endforelse
            </div>
        </section>
    @elseif ($canOpenScoring)
        <div class="card mb-5 text-center text-gray-500 py-8">
            Πατήστε «Έναρξη ψηφοφορίας» για να ενεργοποιηθεί η πρώτη ομάδα.
        </div>
    @endif

    @if ($showCorrectionForm && ($correctingVoteId || $proxyJudgeId))
        @php
            $proxyJudgeName = null;
            if ($proxyJudgeId) {
                foreach ($judgeStatus as $statusItem) {
                    if ($statusItem['judge']->id === $proxyJudgeId) {
                        $proxyJudgeName = $statusItem['judge']->name;
                        break;
                    }
                }
            }
        @endphp
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="correction-title">
            <div class="modal-panel">
                <h3 id="correction-title" class="font-bold text-lg mb-4">
                    @if ($proxyJudgeId)
                        Καταχώρηση βαθμού
                        @if ($proxyJudgeName)
                            <span class="block text-sm font-normal text-gray-500 mt-1">για {{ $proxyJudgeName }}</span>
                        @endif
                    @else
                        Διόρθωση βαθμού
                    @endif
                </h3>
                <div class="space-y-4">
                    <div>
                        <label for="correction-score" class="block text-sm font-medium mb-1">Βαθμός (9 / 10 / 12)</label>
                        <select id="correction-score" wire:model="correctionScore" class="input-touch">
                            <option value="9">9</option>
                            <option value="10">10</option>
                            <option value="12">12</option>
                        </select>
                    </div>
                    <div>
                        <label for="correction-reason" class="block text-sm font-medium mb-1">Αιτιολογία *</label>
                        <textarea id="correction-reason" wire:model="correctionReason" class="input-touch min-h-[100px]" rows="3" required></textarea>
                        @error('correctionReason') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="button" wire:click="correctVote" class="w-full btn-touch bg-indigo-600 text-white">Αποθήκευση</button>
                        <button type="button" wire:click="closeScoreForm" class="w-full btn-touch border border-gray-300">Ακύρωση</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showFinalVoteForm)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="final-vote-title">
            <div class="modal-panel">
                <h3 id="final-vote-title" class="font-bold text-lg mb-4">
                    {{ $finalVoteId ? 'Διόρθωση τελικής ψήφου' : 'Καταχώρηση τελικής ψήφου' }}
                </h3>
                <div class="space-y-4">
                    <div>
                        <label for="final-vote-team" class="block text-sm font-medium mb-1">Ομάδα *</label>
                        <select id="final-vote-team" wire:model="finalVoteTeamId" class="input-touch">
                            <option value="">— Επιλέξτε —</option>
                            @foreach ($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                        @error('finalVoteTeamId') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <p class="block text-sm font-medium mb-1">Βαθμός</p>
                        <p class="input-touch bg-amber-50 text-amber-900 font-bold">11 (σταθερός)</p>
                        <input type="hidden" wire:model="finalVoteScore" value="11">
                    </div>
                    <div>
                        <label for="final-vote-reason" class="block text-sm font-medium mb-1">Αιτιολογία *</label>
                        <textarea id="final-vote-reason" wire:model="finalVoteReason" class="input-touch min-h-[100px]" rows="3" required></textarea>
                        @error('finalVoteReason') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="button" wire:click="saveFinalVote" class="w-full btn-touch bg-amber-600 text-white">Αποθήκευση</button>
                        <button type="button" wire:click="closeFinalVoteForm" class="w-full btn-touch border border-gray-300">Ακύρωση</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showWinnerSelect && count($tiedTeams) > 1)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="tie-title">
            <div class="modal-panel">
                <h3 id="tie-title" class="font-bold text-lg mb-4">Ισοβαθμία — Επιλέξτε νικητή</h3>
                <select id="winner-select" wire:model="selectedWinnerId" class="input-touch mb-4">
                    <option value="">— Επιλέξτε —</option>
                    @foreach ($tiedTeams as $item)
                        <option value="{{ $item['team']->id }}">{{ $item['team']->name }} ({{ $item['total_score'] }})</option>
                    @endforeach
                </select>
                <button type="button" wire:click="startPodiumReveal" class="w-full btn-touch bg-yellow-600 text-white">Έναρξη top 5</button>
            </div>
        </div>
    @endif
</div>
