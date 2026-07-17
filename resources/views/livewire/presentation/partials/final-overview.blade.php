@php
    $completeRanking = collect($ranking)->filter(fn ($item) => $item['is_complete'])->values();
    $chartItems = $completeRanking->sortBy('total_score')->values()->all();
    $winnerTeamId = $winner['team']->id
        ?? ($completeRanking->firstWhere('ranking_position', 1)['team']->id ?? null);
@endphp

<div wire:key="final-overview" class="w-full flex flex-col items-center px-2">
    @if (! empty($showTitle))
        <p class="text-base sm:text-xl text-gray-300 mb-3 sm:mb-5 text-center">{{ $talentShow->title }}</p>
    @endif

    <h1 class="text-2xl sm:text-4xl md:text-5xl font-black text-center mb-6 sm:mb-8">Τελική κατάταξη</h1>

    <div class="w-full max-w-4xl space-y-2 sm:space-y-3 mb-2">
        @foreach ($completeRanking as $item)
            @php $isWinner = $winnerTeamId && $item['team']->id === $winnerTeamId; @endphp
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 p-3 sm:p-5 rounded-xl sm:rounded-2xl bg-gray-900/80 border {{ $isWinner ? 'border-yellow-400/70 ring-1 ring-yellow-400/40' : 'border-white/10' }}">
                <span class="text-2xl sm:text-4xl font-black {{ $isWinner ? 'text-yellow-400' : 'text-indigo-300' }} sm:w-16">
                    {{ $item['ranking_position'] }}η
                </span>
                <span class="flex-1 font-bold text-lg sm:text-2xl break-words">
                    {{ $item['team']->name }}
                    @if ($isWinner)
                        <span class="block sm:inline text-sm sm:text-base font-black text-yellow-400 sm:ml-2">ΝΙΚΗΤΡΙΑ</span>
                    @endif
                </span>
                <span class="text-white font-black shrink-0 text-xl sm:text-3xl tabular-nums">{{ $item['total_score'] }}</span>
            </div>
        @endforeach
    </div>

    @include('livewire.presentation.partials.presentation-chart', [
        'chartItems' => $chartItems,
        'winner' => $winner,
        'winnerTeamId' => $winnerTeamId,
        'talentShow' => $talentShow,
    ])
</div>
