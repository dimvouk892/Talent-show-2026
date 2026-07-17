<div @if (! $showCellForm && ! $showFinalVoteForm) wire:poll.2s="pollResults" @endif>
    @include('partials.admin-show-nav', ['talentShow' => $talentShow])

    @if ($flashSuccess)
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-xl text-sm sm:text-base" role="status">{{ $flashSuccess }}</div>
    @endif
    @if ($flashError)
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-xl text-sm sm:text-base" role="alert">{{ $flashError }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5 sm:mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">Αναλυτικά Αποτελέσματα</h1>
            <p class="text-xs text-green-700 mt-1 flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-green-500 animate-pulse" aria-hidden="true"></span>
                Ζωντανή ενημέρωση
            </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <a href="{{ route('presentation.panel') }}"
               target="_blank"
               rel="noopener"
               class="w-full sm:w-auto btn-touch-sm bg-indigo-600 text-white text-center hover:bg-indigo-500">
                Panel ↗
            </a>
            <a href="{{ route('admin.talent-shows.results.print', $talentShow) }}"
               target="_blank"
               rel="noopener"
               class="w-full sm:w-auto btn-touch-sm bg-gray-800 text-white text-center">
                Εκτύπωση A4
            </a>
            <a href="{{ route('admin.talent-shows.results.export', $talentShow) }}"
               class="w-full sm:w-auto btn-touch-sm bg-indigo-600 text-white text-center">
                Λήψη CSV
            </a>
        </div>
    </div>

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6" aria-label="Σύνοψη">
        <div class="card p-4 text-center sm:text-left">
            <p class="text-xs text-gray-500">Ομάδες</p>
            <p class="text-2xl font-bold">{{ $summary['teams_with_votes'] }}<span class="text-sm text-gray-400">/{{ $summary['total_teams'] }}</span></p>
        </div>
        <div class="card p-4 text-center sm:text-left">
            <p class="text-xs text-gray-500">Ολοκληρωμένες</p>
            <p class="text-2xl font-bold text-green-700">{{ $summary['complete_teams'] }}</p>
        </div>
        <div class="card p-4 text-center sm:text-left">
            <p class="text-xs text-gray-500">Κριτές</p>
            <p class="text-2xl font-bold">{{ $summary['active_judges'] }}</p>
        </div>
        <div class="card p-4 text-center sm:text-left">
            <p class="text-xs text-gray-500">Σύνολο ψήφων</p>
            <p class="text-2xl font-bold">{{ $summary['total_votes'] }}</p>
        </div>
    </section>

    @if ($winner && $talentShow->winner_revealed)
        <div class="card mb-6 bg-yellow-50 border-yellow-200">
            <h2 class="text-lg sm:text-xl font-bold text-yellow-800">Νικήτρια ομάδα: {{ $winner['team']->name }}</h2>
            <p class="mt-1">{{ $winner['total_score'] }}</p>
        </div>
    @endif

    @if (count($tiedTeams) > 1 && ! $talentShow->winner_revealed)
        <div class="card mb-6 bg-orange-50 border-orange-200">
            <p class="font-medium text-orange-800 mb-3">Ισοβαθμία — Επιλέξτε νικητή:</p>
            <div class="flex flex-col sm:flex-row gap-2">
                <label for="tie-winner" class="sr-only">Επιλογή νικητή</label>
                <select id="tie-winner" wire:model="selectedWinnerId" class="input-touch sm:flex-1">
                    <option value="">— Επιλέξτε —</option>
                    @foreach ($tiedTeams as $item)
                        <option value="{{ $item['team']->id }}">{{ $item['team']->name }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="revealWinner" class="w-full sm:w-auto btn-touch bg-yellow-600 text-white">Αποκάλυψη νικητή</button>
            </div>
        </div>
    @endif

    @if ($finalVoter)
        <div class="card mb-6 border-amber-200 bg-amber-50/50">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-amber-900">Τελική ψήφος — {{ $finalVoter->name }}</h2>
                    @if ($finalVote)
                        <p class="text-sm text-amber-800 mt-1">
                            Ομάδα: <strong>{{ $finalVote->team->name }}</strong>
                            · Βαθμός: <strong>{{ $finalVote->score }}</strong>
                            @if ($finalVote->is_admin_edited)<span class="text-xs text-orange-700">*</span>@endif
                        </p>
                    @else
                        <p class="text-sm text-amber-800 mt-1">Δεν έχει υποβληθεί ακόμη.</p>
                    @endif
                </div>
                <button type="button"
                        wire:click="openFinalVoteForm"
                        class="w-full sm:w-auto btn-touch-sm {{ $finalVote ? 'bg-amber-700' : 'bg-amber-600' }} text-white">
                    {{ $finalVote ? 'Διόρθωση' : 'Καταχώρηση' }}
                </button>
            </div>
        </div>
    @endif

    <div class="card p-0 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Πίνακας βαθμολογιών</h2>
            <p class="text-xs text-gray-500 mt-0.5">Κλικ σε βαθμό ή — για διόρθωση / καταχώρηση · οριζόντια κύλιση σε μικρές οθόνες</p>
        </div>

        @if ($judges->isEmpty() || count($ranking) === 0)
            <div class="p-8 text-center text-gray-500">
                <p class="font-medium text-gray-700">Δεν υπάρχουν ακόμα ομάδες ή κριτές.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse min-w-[640px]">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600">
                            <th class="sticky left-0 z-10 bg-gray-50 p-3 text-left font-semibold border-b border-gray-200 min-w-[48px]">#</th>
                            <th class="sticky left-10 z-10 bg-gray-50 p-3 text-left font-semibold border-b border-gray-200 min-w-[160px]">Ομάδα</th>
                            @foreach ($judges as $judge)
                                <th class="p-3 text-center font-semibold border-b border-gray-200 whitespace-nowrap min-w-[72px] {{ $judge->is_final_voter ? 'bg-amber-50 text-amber-900' : '' }}"
                                    title="{{ $judge->name }}{{ $judge->is_final_voter ? ' (τελική ψήφος)' : '' }}">
                                    <span class="block max-w-[6rem] mx-auto truncate">{{ $judge->name }}</span>
                                    @if ($judge->is_final_voter)
                                        <span class="block text-[10px] font-normal text-amber-700 mt-0.5">τελική</span>
                                    @endif
                                </th>
                            @endforeach
                            <th class="p-3 text-center font-semibold border-b border-gray-200 bg-indigo-50 text-indigo-900 min-w-[88px]">Σύνολο</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ranking as $item)
                            <tr class="border-b border-gray-100 {{ ! $item['is_complete'] && $item['votes_count'] > 0 ? 'bg-orange-50/50' : 'hover:bg-gray-50/80' }}">
                                <td class="sticky left-0 z-10 bg-white p-3 font-bold text-indigo-600">
                                    @if ($item['ranking_position'])
                                        {{ $item['ranking_position'] }}
                                    @else
                                        <span class="text-gray-300 font-normal">—</span>
                                    @endif
                                </td>
                                <td class="sticky left-10 z-10 bg-white p-3 font-medium">
                                    {{ $item['team']->name }}
                                </td>
                                @foreach ($judges as $judge)
                                    @php
                                        $score = collect($item['judge_scores'])->firstWhere('judge_id', $judge->id);
                                        $hasVoted = $score && $score['has_voted'];
                                    @endphp
                                    <td class="p-1 text-center tabular-nums {{ $judge->is_final_voter ? 'bg-amber-50/40' : '' }}">
                                        <button type="button"
                                                wire:click="openCellEdit({{ $item['team']->id }}, {{ $judge->id }})"
                                                title="{{ $hasVoted ? 'Διόρθωση βαθμού' : 'Καταχώρηση βαθμού' }}"
                                                class="w-full min-h-[2.75rem] rounded-lg px-1 py-2 transition touch-manipulation
                                                       hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400">
                                            @if ($hasVoted)
                                                <span class="inline-flex items-center justify-center min-w-[2rem] font-bold text-base {{ $judge->is_final_voter ? 'text-amber-800' : 'text-indigo-700' }}">
                                                    {{ $score['score'] }}@if ($score['is_admin_edited'])<span class="text-orange-600 text-xs">*</span>@endif
                                                </span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </button>
                                    </td>
                                @endforeach
                                <td class="p-3 text-center font-bold bg-indigo-50/60 tabular-nums">
                                    @if ($item['votes_count'] > 0 || collect($item['judge_scores'])->contains('has_voted', true))
                                        {{ $item['total_score'] }}
                                    @else
                                        <span class="text-gray-300 font-normal">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="p-3 text-xs text-gray-500 border-t border-gray-100">* Βαθμός διορθωμένος από διαχειριστή · κλικ σε κελί για αλλαγή</p>
        @endif
    </div>

    @if ($showCellForm)
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="cell-edit-title">
            <div class="modal-panel">
                <h3 id="cell-edit-title" class="font-bold text-lg mb-1">
                    {{ $cellHadVote ? 'Διόρθωση βαθμού' : 'Καταχώρηση βαθμού' }}
                </h3>
                <p class="text-sm text-gray-500 mb-4">
                    {{ $cellTeamName }} · {{ $cellJudgeName }}
                </p>
                <div class="space-y-4">
                    <div>
                        <label for="cell-score" class="block text-sm font-medium mb-1">Βαθμός (9 / 10 / 12)</label>
                        <select id="cell-score" wire:model="cellScore" class="input-touch">
                            <option value="9">9</option>
                            <option value="10">10</option>
                            <option value="12">12</option>
                        </select>
                    </div>
                    <div>
                        <label for="cell-reason" class="block text-sm font-medium mb-1">Αιτιολογία *</label>
                        <textarea id="cell-reason" wire:model="cellReason" class="input-touch min-h-[100px]" rows="3" required></textarea>
                        @error('cellReason') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="button" wire:click="saveCellScore" class="w-full btn-touch bg-indigo-600 text-white">Αποθήκευση</button>
                        <button type="button" wire:click="closeCellForm" class="w-full btn-touch border border-gray-300">Ακύρωση</button>
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
                        <label for="final-vote-score" class="block text-sm font-medium mb-1">Βαθμός (9 / 10 / 12)</label>
                        <select id="final-vote-score" wire:model="finalVoteScore" class="input-touch">
                            <option value="9">9</option>
                            <option value="10">10</option>
                            <option value="12">12</option>
                        </select>
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
</div>
