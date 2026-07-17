@php
    $current = $podium['current'] ?? null;
    $revealed = $podium['revealed'] ?? [];
    $isFirstPlace = $current && (int) $current['ranking_position'] === 1;
@endphp

@if (($podium['step'] ?? 0) < 1)
    <p class="text-xl sm:text-3xl md:text-4xl text-gray-500 text-center px-4">Αναμονή αποκάλυψης top 5...</p>
@else
    <div wire:key="{{ $sceneKey }}"
         class="w-full flex flex-col items-center"
         x-data
         x-init="$nextTick(() => {
             $el.classList.add('screen-scene-enter');
             $el.addEventListener('animationend', (event) => {
                 if (event.target === $el) {
                     $el.classList.remove('screen-scene-enter');
                 }
             }, { once: true });
         })">
        @if (! empty($showTitle))
            <p class="text-base sm:text-xl text-gray-400 mb-4 sm:mb-6 text-center">{{ $talentShow->title }}</p>
        @endif

        @if ($current)
            <div class="text-center px-2 w-full mb-8 sm:mb-10">
                <p class="text-2xl sm:text-4xl md:text-5xl font-black mb-3 sm:mb-4 {{ $isFirstPlace ? 'text-yellow-400' : 'text-indigo-300' }}">
                    {{ $current['ranking_position'] }}η ΘΕΣΗ
                </p>
                @if ($isFirstPlace)
                    <h1 class="text-2xl sm:text-4xl md:text-5xl font-black text-yellow-400 mb-4 sm:mb-6 animate-pulse">ΝΙΚΗΤΡΙΑ ΟΜΑΔΑ</h1>
                @endif
                <h2 class="text-3xl sm:text-5xl md:text-6xl font-bold mb-4 sm:mb-6 break-words leading-tight">
                    {{ $current['team']->name }}
                </h2>
                <p class="text-2xl sm:text-4xl md:text-5xl font-black text-white tabular-nums">
                    {{ $current['total_score'] }}
                </p>
            </div>
        @endif

        @if (count($revealed) > 1)
            <div class="w-full max-w-3xl space-y-2 sm:space-y-3">
                <p class="text-sm sm:text-base text-gray-300 uppercase tracking-wide text-center mb-2">Βαθμολογία</p>
                @foreach (array_reverse($revealed) as $item)
                    @php $isLatest = $current && $item['team']->id === $current['team']->id; @endphp
                    @if (! $isLatest)
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 p-3 sm:p-4 bg-gray-900/80 rounded-xl text-base sm:text-xl {{ (int) $item['ranking_position'] === 1 ? 'ring-2 ring-yellow-400/60' : '' }}">
                            <span class="font-black text-indigo-300 sm:w-14">{{ $item['ranking_position'] }}η</span>
                            <span class="flex-1 font-semibold text-white break-words">{{ $item['team']->name }}</span>
                            <span class="text-white font-black shrink-0 text-xl sm:text-2xl tabular-nums">{{ $item['total_score'] }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@endif
