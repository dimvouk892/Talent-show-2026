<div>
    @include('partials.admin-show-nav', ['talentShow' => $talentShow])

    @if ($flashSuccess)
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-xl text-sm sm:text-base" role="status">{{ $flashSuccess }}</div>
    @endif
    @if ($flashError)
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-xl text-sm sm:text-base" role="alert">{{ $flashError }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5 sm:mb-6">
        <h1 class="text-xl sm:text-2xl font-bold">Αναλυτικά Αποτελέσματα</h1>
        @if (count($ranking) > 0)
            <div class="flex flex-col sm:flex-row gap-2">
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
        @endif
    </div>

    @if (count($ranking) === 0)
        <div class="card text-center text-gray-500 py-10">
            <p class="font-medium text-gray-700">Δεν υπάρχουν ακόμα βαθμολογίες.</p>
            <p class="text-sm mt-2">Οι ομάδες εμφανίζονται εδώ μόλις ψηφίσει τουλάχιστον ένας κριτής.</p>
        </div>
    @else
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6" aria-label="Σύνοψη">
            <div class="card p-4 text-center sm:text-left">
                <p class="text-xs text-gray-500">Ομάδες με ψήφους</p>
                <p class="text-2xl font-bold">{{ $summary['teams_with_votes'] }}<span class="text-sm text-gray-400">/{{ $summary['total_teams'] }}</span></p>
            </div>
            <div class="card p-4 text-center sm:text-left">
                <p class="text-xs text-gray-500">Ολοκληρωμένες</p>
                <p class="text-2xl font-bold text-green-700">{{ $summary['complete_teams'] }}</p>
            </div>
            <div class="card p-4 text-center sm:text-left">
                <p class="text-xs text-gray-500">Ενεργοί κριτές</p>
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
                <p class="mt-1">{{ $winner['total_score'] }} / {{ $winner['maximum_score'] }} — Μ.Ο. {{ number_format($winner['average_score'], 2, ',', '') }}</p>
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

        <div class="admin-cards-mobile space-y-4">
            @foreach ($ranking as $item)
                <article class="card {{ ! $item['is_complete'] ? 'border-dashed border-orange-200' : '' }}">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            @if ($item['ranking_position'])
                                <span class="text-2xl font-black text-indigo-600">{{ $item['ranking_position'] }}η</span>
                            @else
                                <span class="text-sm font-semibold text-orange-600">Μερικό αποτέλεσμα</span>
                            @endif
                            <h3 class="font-bold text-lg mt-1">{{ $item['team']->name }}</h3>
                            <p class="text-xs text-gray-500 mt-1">Σειρά {{ $item['team']->display_order }}</p>
                        </div>
                        <div class="text-right text-sm shrink-0">
                            <p class="text-lg font-bold">{{ $item['total_score'] }} / {{ $item['maximum_score'] }}</p>
                            <p class="text-gray-500">Μ.Ο. {{ number_format($item['average_score'], 2, ',', '') }}</p>
                            <p class="text-gray-400 text-xs">{{ $item['votes_count'] }}/{{ $item['active_judges_count'] }} κριτές</p>
                            <p class="text-gray-400 text-xs">{{ $item['number_of_tens'] }}×10 · {{ $item['number_of_nines'] }}×9</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($item['judge_scores'] as $score)
                            <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg text-sm">
                                <span>
                                    {{ $score['judge_name'] }}
                                </span>
                                @if ($score['has_voted'])
                                    <span class="font-bold text-indigo-600">
                                        {{ $score['score'] }}
                                        @if ($score['is_admin_edited'])
                                            <span class="text-xs text-orange-600">*</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </div>

        <div class="hidden md:block card p-0 overflow-hidden mt-6">
            <div class="overflow-x-auto">
                <table class="admin-table-desktop text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-3 text-left sticky left-0 bg-gray-50">Θέση</th>
                            <th class="p-3 text-left sticky left-12 bg-gray-50 min-w-[140px]">Ομάδα</th>
                            @foreach ($judges as $judge)
                                <th class="p-3 text-center whitespace-nowrap" title="{{ $judge->name }}">
                                    {{ $judge->name }}
                                </th>
                            @endforeach
                            <th class="p-3 text-center">Σύνολο</th>
                            <th class="p-3 text-center">Μ.Ο.</th>
                            <th class="p-3 text-center">10/9</th>
                            <th class="p-3 text-center">Ψήφοι</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ranking as $item)
                            <tr class="border-t {{ ! $item['is_complete'] ? 'bg-orange-50/40' : '' }}">
                                <td class="p-3 font-bold sticky left-0 bg-white">
                                    @if ($item['ranking_position'])
                                        {{ $item['ranking_position'] }}η
                                    @else
                                        <span class="text-orange-600 text-xs">Μερικό</span>
                                    @endif
                                </td>
                                <td class="p-3 font-medium sticky left-12 bg-white min-w-[140px]">
                                    {{ $item['team']->name }}
                                    <span class="block text-xs text-gray-400">Σειρά {{ $item['team']->display_order }}</span>
                                </td>
                                @foreach ($judges as $judge)
                                    @php
                                        $score = collect($item['judge_scores'])->firstWhere('judge_id', $judge->id);
                                    @endphp
                                    <td class="p-3 text-center font-semibold">
                                        @if ($score && $score['has_voted'])
                                            {{ $score['score'] }}@if ($score['is_admin_edited'])<span class="text-orange-600">*</span>@endif
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="p-3 text-center font-bold">{{ $item['total_score'] }}/{{ $item['maximum_score'] }}</td>
                                <td class="p-3 text-center">{{ number_format($item['average_score'], 2, ',', '') }}</td>
                                <td class="p-3 text-center text-xs">{{ $item['number_of_tens'] }}/{{ $item['number_of_nines'] }}</td>
                                <td class="p-3 text-center text-xs">{{ $item['votes_count'] }}/{{ $item['active_judges_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="p-3 text-xs text-gray-500 border-t">* Βαθμός διορθωμένος από διαχειριστή</p>
        </div>
    @endif
</div>
