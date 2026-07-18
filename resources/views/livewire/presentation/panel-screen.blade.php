<div class="relative min-h-screen w-full max-w-[100vw] mx-auto">
    @include('livewire.presentation.partials.presentation-background', ['talentShow' => $talentShow])

    <div wire:poll.2s="pollPanel" class="relative z-10 flex flex-col p-3 sm:p-5 md:p-6 min-h-screen">
        @include('livewire.presentation.partials.scoreboard', [
            'talentShow' => $talentShow,
            'judges' => $judges,
            'ranking' => $ranking,
            'winner' => $winner,
            'showEventTitle' => true,
            'showScoreboardTitle' => true,
            'showLiveBadge' => true,
        ])
    </div>
</div>
