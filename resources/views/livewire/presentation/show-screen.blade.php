<div wire:poll.2s class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 md:p-8 w-full max-w-6xl mx-auto overflow-x-hidden">
    <p class="text-base sm:text-xl md:text-2xl text-gray-400 mb-2 sm:mb-4 text-center break-words w-full">{{ $talentShow->title }}</p>

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
    @if ($talentShow->showing_closing_video && $talentShow->closing_video_path)
        @include('livewire.presentation.partials.fullscreen-video', [
            'videoUrl' => $talentShow->closingVideoUrl(),
            'wireKey' => 'closing-'.$talentShow->id.'-'.$talentShow->closing_video_path,
            'finishMethod' => 'finishClosingVideo',
            'label' => 'Video λήξης',
        ])
    @elseif ($talentShow->showing_opening_video && $talentShow->opening_video_path)
        @include('livewire.presentation.partials.fullscreen-video', [
            'videoUrl' => $talentShow->openingVideoUrl(),
            'wireKey' => 'opening-'.$talentShow->id.'-'.$talentShow->opening_video_path,
            'finishMethod' => 'finishOpeningVideo',
            'label' => 'Video έναρξης',
        ])
    @elseif ($winner && $talentShow->winner_revealed)
        <div class="text-center relative w-full px-2" x-data x-init="
            for (let i = 0; i < 50; i++) {
                let el = document.createElement('div');
                el.className = 'confetti';
                el.style.left = Math.random() * 100 + 'vw';
                el.style.backgroundColor = ['#f00','#0f0','#00f','#ff0','#f0f'][Math.floor(Math.random()*5)];
                el.style.animationDelay = Math.random() * 2 + 's';
                document.body.appendChild(el);
            }
        ">
            <h1 class="text-3xl sm:text-5xl md:text-6xl font-black text-yellow-400 mb-4 sm:mb-8">ΝΙΚΗΤΡΙΑ ΟΜΑΔΑ</h1>
            @if ($winner['team']->photo_path)
                <img src="{{ $winner['team']->photoUrl() }}" alt="{{ $winner['team']->name }}" class="screen-media w-32 h-32 sm:w-48 sm:h-48 mx-auto rounded-2xl object-cover mb-4 sm:mb-6">
            @endif
            <h2 class="text-2xl sm:text-4xl md:text-5xl font-bold mb-3 sm:mb-4 break-words">{{ $winner['team']->name }}</h2>
            <p class="text-xl sm:text-2xl md:text-3xl">Τελικό σκορ: {{ $winner['total_score'] }} / {{ $winner['maximum_score'] }}</p>
            <p class="text-lg sm:text-xl md:text-2xl text-gray-400 mt-2">Μέσος όρος: {{ number_format($winner['average_score'], 2, ',', '') }} / 10</p>
        </div>
    @elseif ($talentShow->show_ranking && count($ranking) > 0)
        <h1 class="text-2xl sm:text-4xl md:text-5xl font-bold mb-6 sm:mb-12 text-center">Τελική κατάταξη</h1>
        <div class="w-full space-y-3 sm:space-y-4">
            @foreach ($ranking as $item)
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 p-4 sm:p-6 bg-gray-900 rounded-xl sm:rounded-2xl text-lg sm:text-2xl">
                    <span class="text-2xl sm:text-4xl font-black text-indigo-400 sm:w-16">{{ $item['ranking_position'] }}η</span>
                    <span class="flex-1 font-bold break-words">{{ $item['team']->name }}</span>
                    <span class="text-indigo-300 shrink-0">{{ $item['total_score'] }} / {{ $item['maximum_score'] }}</span>
                </div>
            @endforeach
        </div>
    @elseif ($currentTeam && $talentShow->showing_team_intro && $currentTeam->video_path)
        @include('livewire.presentation.partials.fullscreen-video', [
            'videoUrl' => $currentTeam->videoUrl(),
            'wireKey' => 'team-intro-'.$currentTeam->id.'-'.$currentTeam->video_path,
            'finishMethod' => 'finishIntro',
            'label' => 'Intro video — '.$currentTeam->name,
        ])
    @elseif ($currentTeam)
        <div class="text-center w-full px-2">
            <p class="text-lg sm:text-2xl md:text-3xl text-gray-400 mb-3 sm:mb-4">Τρέχουσα ομάδα</p>
            @if ($currentTeam->photo_path)
                <img src="{{ $currentTeam->photoUrl() }}" alt="{{ $currentTeam->name }}"
                     class="screen-media w-40 h-40 sm:w-56 sm:h-56 md:w-64 md:h-64 mx-auto rounded-2xl sm:rounded-3xl object-cover mb-4 sm:mb-8 shadow-2xl">
            @endif
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
                <p class="text-xl sm:text-3xl md:text-4xl font-bold">
                    @if ($scores['is_complete'])
                        Συνολικό σκορ: {{ $scores['total_score'] }} / {{ $scores['maximum_score'] }}
                    @else
                        Προσωρινό σκορ: {{ $scores['total_score'] }}
                    @endif
                </p>
                <p class="text-lg sm:text-2xl md:text-3xl text-gray-400 mt-2">
                    Μέσος όρος: {{ number_format($scores['average_score'], 2, ',', '') }} / 10
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
    @else
        @if ($talentShow->shouldDisplayWaitingVideo())
            @include('livewire.presentation.partials.fullscreen-video', [
                'videoUrl' => $talentShow->waitingVideoUrl(),
                'wireKey' => 'waiting-'.$talentShow->id.'-'.$talentShow->waiting_video_path,
                'label' => null,
                'loop' => true,
            ])
        @elseif ($talentShow->shouldDisplayWaitingImage())
            <div class="text-center w-full px-2">
                <img src="{{ $talentShow->waitingImageUrl() }}" alt=""
                     class="screen-media w-full max-w-4xl mx-auto rounded-2xl shadow-2xl object-contain max-h-[80vh]">
            </div>
        @else
            <h1 class="text-2xl sm:text-4xl md:text-5xl font-bold text-gray-500 text-center">Αναμονή έναρξης...</h1>
        @endif
    @endif
    </div>
</div>
