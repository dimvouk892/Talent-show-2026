<div wire:poll.2s="pollPanel" class="min-h-screen flex flex-col p-3 sm:p-6 md:p-8 w-full max-w-[100vw] mx-auto overflow-x-hidden">
    <header class="mb-4 sm:mb-6 text-center">
        <p class="text-sm sm:text-lg text-gray-400 break-words">{{ $talentShow->title }}</p>
        <h1 class="text-2xl sm:text-4xl md:text-5xl font-black tracking-tight mt-1">Πίνακας βαθμολογιών</h1>
        <p class="mt-2 text-xs sm:text-sm text-green-400/90 flex items-center justify-center gap-1.5">
            <span class="inline-block w-2 h-2 rounded-full bg-green-400 animate-pulse" aria-hidden="true"></span>
            Ζωντανή ενημέρωση
        </p>
    </header>

    @if ($winner && $talentShow->winner_revealed)
        <div class="mb-4 sm:mb-6 mx-auto w-full max-w-3xl rounded-2xl border border-yellow-500/40 bg-yellow-500/10 px-4 py-3 sm:px-6 sm:py-4 text-center">
            <p class="text-sm sm:text-base text-yellow-300/90 uppercase tracking-wide font-semibold">Νικήτρια ομάδα</p>
            <p class="text-xl sm:text-3xl font-black text-yellow-300 break-words">{{ $winner['team']->name }}</p>
            <p class="text-sm sm:text-lg text-yellow-100/80 mt-1">
                {{ $winner['total_score'] }} / {{ $winner['maximum_score'] }}
                · Μ.Ο. {{ number_format($winner['average_score'], 2, ',', '') }}
            </p>
        </div>
    @endif

    @if ($judges->isEmpty() || count($ranking) === 0)
        <div class="flex-1 flex items-center justify-center">
            <p class="text-xl sm:text-3xl text-gray-500 text-center px-4">Δεν υπάρχουν ακόμα ομάδες ή κριτές.</p>
        </div>
    @else
        <div class="flex-1 w-full overflow-x-auto rounded-2xl border border-gray-800 bg-gray-950/80 shadow-2xl">
            <table class="w-full text-sm sm:text-base md:text-lg border-collapse min-w-[720px]">
                <thead>
                    <tr class="bg-gray-900 text-gray-300">
                        <th class="sticky left-0 z-10 bg-gray-900 p-3 sm:p-4 text-left font-semibold border-b border-gray-800 min-w-[48px]">#</th>
                        <th class="sticky left-10 z-10 bg-gray-900 p-3 sm:p-4 text-left font-semibold border-b border-gray-800 min-w-[140px]">Ομάδα</th>
                        @foreach ($judges as $judge)
                            <th class="p-3 sm:p-4 text-center font-semibold border-b border-gray-800 whitespace-nowrap min-w-[72px] {{ $judge->is_final_voter ? 'bg-amber-950/50 text-amber-200' : '' }}"
                                title="{{ $judge->name }}{{ $judge->is_final_voter ? ' (τελική ψήφος)' : '' }}">
                                <span class="block max-w-[7rem] mx-auto truncate">{{ $judge->name }}</span>
                                @if ($judge->is_final_voter)
                                    <span class="block text-[10px] sm:text-xs font-normal text-amber-400/90 mt-0.5">τελική</span>
                                @endif
                            </th>
                        @endforeach
                        <th class="p-3 sm:p-4 text-center font-semibold border-b border-gray-800 bg-indigo-950/60 text-indigo-200 min-w-[96px]">Σύνολο</th>
                        <th class="p-3 sm:p-4 text-center font-semibold border-b border-gray-800 min-w-[72px]">Μ.Ο.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ranking as $item)
                        @php
                            $hasAnyVote = $item['votes_count'] > 0
                                || collect($item['judge_scores'])->contains('has_voted', true);
                        @endphp
                        <tr class="border-b border-gray-800/80 {{ ! $item['is_complete'] && $hasAnyVote ? 'bg-orange-950/20' : '' }}">
                            <td class="sticky left-0 z-10 bg-gray-950 p-3 sm:p-4 font-black text-indigo-400">
                                @if ($item['ranking_position'])
                                    {{ $item['ranking_position'] }}
                                @else
                                    <span class="text-gray-600 font-normal">—</span>
                                @endif
                            </td>
                            <td class="sticky left-10 z-10 bg-gray-950 p-3 sm:p-4 font-semibold break-words">
                                {{ $item['team']->name }}
                            </td>
                            @foreach ($judges as $judge)
                                @php
                                    $score = collect($item['judge_scores'])->firstWhere('judge_id', $judge->id);
                                @endphp
                                <td class="p-3 sm:p-4 text-center tabular-nums {{ $judge->is_final_voter ? 'bg-amber-950/30' : '' }}">
                                    @if ($score && $score['has_voted'])
                                        <span class="inline-flex items-center justify-center min-w-[2rem] font-bold text-base sm:text-xl {{ $judge->is_final_voter ? 'text-amber-300' : 'text-indigo-300' }}">
                                            {{ $score['score'] }}
                                        </span>
                                    @else
                                        <span class="text-gray-600">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="p-3 sm:p-4 text-center font-black bg-indigo-950/40 tabular-nums text-indigo-200">
                                @if ($hasAnyVote)
                                    {{ $item['total_score'] }}<span class="text-gray-500 font-normal text-xs sm:text-sm">/{{ $item['maximum_score'] }}</span>
                                @else
                                    <span class="text-gray-600 font-normal">—</span>
                                @endif
                            </td>
                            <td class="p-3 sm:p-4 text-center tabular-nums text-gray-200">
                                @if ($hasAnyVote)
                                    {{ number_format($item['average_score'], 2, ',', '') }}
                                @else
                                    <span class="text-gray-600">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
