<div wire:poll.2s class="relative min-h-screen w-full max-w-6xl mx-auto overflow-x-hidden">
    @include('livewire.presentation.partials.presentation-background', ['talentShow' => $talentShow])

    <div class="relative z-10 flex flex-col items-center justify-center p-4 sm:p-6 md:p-8 min-h-screen bg-black/30">
    <p class="text-base sm:text-xl md:text-2xl text-gray-300 mb-2 sm:mb-4 text-center break-words w-full">{{ $talentShow->title }}</p>

    <div wire:key="presentation-scene-{{ $presentationScene }}"
         x-data
         x-init="$nextTick(() => {
             $el.classList.add('screen-scene-enter');
             $el.addEventListener('animationend', (event) => {
                 if (event.target === $el) {
                     $el.classList.remove('screen-scene-enter');
                 }
             }, { once: true });
         })"
         class="relative flex flex-col items-center justify-center w-full">
    @if ($talentShow->show_final_overview)
        @include('livewire.presentation.partials.final-overview', [
            'talentShow' => $talentShow,
            'ranking' => $ranking,
            'winner' => $winner,
            'showTitle' => false,
        ])
    @elseif (($podium['step'] ?? 0) > 0)
        @include('livewire.presentation.partials.podium-reveal', [
            'talentShow' => $talentShow,
            'podium' => $podium,
            'sceneKey' => $presentationScene,
            'showTitle' => false,
        ])
    @elseif ($talentShow->show_ranking)
        <h1 class="text-2xl sm:text-4xl md:text-5xl font-bold text-gray-300 text-center px-4">Αναμονή αποκάλυψης top 5...</h1>
    @elseif ($currentTeam)
        <div class="text-center w-full px-2">
            <p class="text-lg sm:text-2xl md:text-3xl text-gray-400 mb-3 sm:mb-4">Τρέχουσα ομάδα</p>
            <h1 class="text-3xl sm:text-5xl md:text-7xl font-black mb-4 sm:mb-8 break-words leading-tight">{{ $currentTeam->name }}</h1>

            @if ($talentShow->show_live_scores && $scores && $scores['votes_count'] > 0)
                <div class="w-full">
                <div class="grid gap-2 sm:gap-3 max-w-2xl mx-auto mb-6 sm:mb-8 w-full">
                    @foreach ($judgeStatus as $item)
                        @if ($item['has_voted'])
                            <div class="flex justify-between text-lg sm:text-2xl md:text-3xl p-3 sm:p-4 bg-gray-900 rounded-xl gap-4">
                                <span class="text-left min-w-0">
                                    {{ $item['judge']->name }}
                                </span>
                                <span class="font-bold text-indigo-400 shrink-0">{{ $item['score'] }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
                <p class="text-xl sm:text-3xl md:text-4xl font-black text-white tabular-nums">
                    @if ($scores['is_complete'])
                        Συνολικό σκορ: {{ $scores['total_score'] }}
                    @else
                        Προσωρινό σκορ: {{ $scores['total_score'] }}
                    @endif
                </p>
                @if (! $scores['is_complete'])
                    <p class="text-base sm:text-xl text-indigo-300 mt-3">
                        Έχουν ψηφίσει {{ $scores['votes_count'] }} από {{ $scores['active_judges_count'] }} κριτές
                    </p>
                @endif
                </div>
            @else
                <p class="text-xl sm:text-2xl md:text-4xl text-indigo-300">
                    Έχουν ψηφίσει {{ $voteProgress['voted'] }} από {{ $voteProgress['total'] }} κριτές
                </p>
            @endif
        </div>
    @elseif (in_array($talentShow->status->value, ['scoring_closed', 'results_ready', 'winner_revealed'], true))
        <h1 class="text-2xl sm:text-4xl md:text-5xl font-bold text-gray-500 text-center">Αναμονή αποτελεσμάτων...</h1>
    @else
        <h1 class="text-2xl sm:text-4xl md:text-5xl font-bold text-gray-500 text-center">Αναμονή έναρξης...</h1>
    @endif
    </div>
    </div>
</div>
