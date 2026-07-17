<div wire:poll.2s="pollPanel" class="relative min-h-screen w-full max-w-[100vw] mx-auto">
    @include('livewire.presentation.partials.presentation-background', ['talentShow' => $talentShow])

    <div class="relative z-10 flex flex-col p-3 sm:p-5 md:p-6 min-h-screen">
        <header class="mb-3 sm:mb-5 text-center">
            <p class="text-sm sm:text-base text-gray-300 break-words">{{ $talentShow->title }}</p>
            <h1 class="text-2xl sm:text-4xl md:text-5xl font-black tracking-tight mt-1">Πίνακας βαθμολογιών</h1>
            <p class="mt-2 text-xs sm:text-sm text-green-400/90 flex items-center justify-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-green-400 animate-pulse" aria-hidden="true"></span>
                Ζωντανή ενημέρωση
            </p>
        </header>

        @if ($winner && $talentShow->winner_revealed)
            <div class="mb-4 sm:mb-5 mx-auto w-full max-w-3xl rounded-2xl border border-yellow-500/40 bg-yellow-500/10 px-4 py-3 sm:px-6 sm:py-4 text-center">
                <p class="text-sm sm:text-base text-yellow-300/90 uppercase tracking-wide font-semibold">Νικήτρια ομάδα</p>
                <p class="text-xl sm:text-3xl font-black text-yellow-300 break-words">{{ $winner['team']->name }}</p>
                <p class="text-sm sm:text-lg text-yellow-100/80 mt-1 tabular-nums">
                    {{ $winner['total_score'] }}
                </p>
            </div>
        @endif

        @if ($judges->isEmpty() || count($ranking) === 0)
            <div class="flex-1 flex items-center justify-center py-16">
                <p class="text-xl sm:text-3xl text-gray-500 text-center px-4">Δεν υπάρχουν ακόμα ομάδες ή κριτές.</p>
            </div>
        @else
            {{-- Mobile: cards --}}
            <div class="lg:hidden space-y-3 pb-4">
                @foreach ($ranking as $item)
                    @php
                        $hasAnyVote = $item['votes_count'] > 0
                            || collect($item['judge_scores'])->contains('has_voted', true);
                    @endphp
                    <article class="rounded-xl border border-white/10 px-3 py-3 {{ ! $item['is_complete'] && $hasAnyVote ? 'border-orange-700/50' : '' }}"
                             style="background-color: rgba(0, 0, 0, 0.8);">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="min-w-0 flex items-baseline gap-2">
                                <span class="shrink-0 text-xl font-black text-indigo-400 tabular-nums">
                                    {{ $item['ranking_position'] ?: '—' }}
                                </span>
                                <h2 class="font-semibold text-base sm:text-lg leading-snug break-words">{{ $item['team']->name }}</h2>
                            </div>
                            <p class="shrink-0 text-xl font-black tabular-nums text-white">
                                {{ $hasAnyVote ? $item['total_score'] : '—' }}
                            </p>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach ($judges as $judge)
                                @php
                                    $score = collect($item['judge_scores'])->firstWhere('judge_id', $judge->id);
                                @endphp
                                <div class="rounded-lg px-2 py-1.5 text-center border border-white/10 {{ $judge->is_final_voter ? 'bg-amber-950/50 border-amber-700/40' : 'bg-gray-900' }}">
                                    <p class="text-[11px] text-gray-400 leading-tight break-words">{{ $judge->name }}</p>
                                    @if ($score && $score['has_voted'])
                                        <p class="text-lg font-bold tabular-nums mt-0.5 {{ $judge->is_final_voter ? 'text-amber-300' : 'text-indigo-300' }}">{{ $score['score'] }}</p>
                                    @else
                                        <p class="text-lg text-gray-600 mt-0.5">—</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Desktop: table --}}
            <div class="hidden lg:block w-full rounded-2xl border border-white/15 shadow-2xl overflow-x-auto"
                 style="background-color: rgba(0, 0, 0, 0.8);">
                <table class="w-full text-base xl:text-lg border-collapse">
                    <thead>
                        <tr class="bg-black/60 text-gray-200">
                            <th class="p-3 xl:p-4 text-left font-semibold border-b border-white/15 whitespace-nowrap">#</th>
                            <th class="p-3 xl:p-4 text-left font-semibold border-b border-white/15 min-w-[10rem]">Ομάδα</th>
                            @foreach ($judges as $judge)
                                <th class="p-3 xl:p-4 text-center font-semibold border-b border-white/15 whitespace-nowrap {{ $judge->is_final_voter ? 'bg-amber-950/50 text-amber-200' : '' }}"
                                    title="{{ $judge->name }}{{ $judge->is_final_voter ? ' (τελική ψήφος)' : '' }}">
                                    <span class="block">{{ $judge->name }}</span>
                                    @if ($judge->is_final_voter)
                                        <span class="block text-xs font-normal text-amber-400/90 mt-0.5">τελική</span>
                                    @endif
                                </th>
                            @endforeach
                            <th class="p-3 xl:p-4 text-center font-semibold border-b border-white/15 bg-indigo-950/60 text-indigo-200 whitespace-nowrap">Σύνολο</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ranking as $item)
                            @php
                                $hasAnyVote = $item['votes_count'] > 0
                                    || collect($item['judge_scores'])->contains('has_voted', true);
                            @endphp
                            <tr class="border-b border-white/10 {{ ! $item['is_complete'] && $hasAnyVote ? 'bg-orange-950/40' : '' }}">
                                <td class="p-3 xl:p-4 font-black text-indigo-400 text-xl xl:text-2xl tabular-nums whitespace-nowrap">
                                    {{ $item['ranking_position'] ?: '—' }}
                                </td>
                                <td class="p-3 xl:p-4 font-semibold text-base xl:text-xl break-words">
                                    {{ $item['team']->name }}
                                </td>
                                @foreach ($judges as $judge)
                                    @php
                                        $score = collect($item['judge_scores'])->firstWhere('judge_id', $judge->id);
                                    @endphp
                                    <td class="p-3 xl:p-4 text-center tabular-nums whitespace-nowrap {{ $judge->is_final_voter ? 'bg-amber-950/35' : '' }}">
                                        @if ($score && $score['has_voted'])
                                            <span class="font-bold text-xl xl:text-2xl {{ $judge->is_final_voter ? 'text-amber-300' : 'text-indigo-300' }}">
                                                {{ $score['score'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 text-xl">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="p-3 xl:p-4 text-center font-black bg-indigo-950/45 tabular-nums text-white text-xl xl:text-2xl whitespace-nowrap">
                                    {{ $hasAnyVote ? $item['total_score'] : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
