<div wire:poll.2s class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-8 w-full max-w-4xl mx-auto overflow-x-hidden">
    @if ($revealed && $winner)
        <div class="text-center px-2 w-full" x-data x-init="
            for (let i = 0; i < 60; i++) {
                let el = document.createElement('div');
                el.className = 'confetti';
                el.style.left = Math.random() * 100 + 'vw';
                el.style.backgroundColor = ['#f00','#0f0','#00f','#ff0','#f0f','#0ff'][Math.floor(Math.random()*6)];
                el.style.animationDelay = Math.random() * 2 + 's';
                document.body.appendChild(el);
            }
        ">
            <h1 class="text-3xl sm:text-5xl md:text-6xl font-black text-yellow-400 mb-6 sm:mb-8 animate-pulse">ΝΙΚΗΤΡΙΑ ΟΜΑΔΑ</h1>
            @if ($winner['team']->photo_path)
                <img src="{{ $winner['team']->photoUrl() }}" alt="{{ $winner['team']->name }}"
                     class="w-36 h-36 sm:w-56 sm:h-56 mx-auto rounded-2xl sm:rounded-3xl object-cover mb-6 sm:mb-8 shadow-2xl">
            @endif
            <h2 class="text-3xl sm:text-5xl md:text-6xl font-bold mb-4 sm:mb-6 break-words">{{ $winner['team']->name }}</h2>
            <p class="text-xl sm:text-3xl md:text-4xl font-bold">Τελικό σκορ: {{ $winner['total_score'] }} / {{ $winner['maximum_score'] }}</p>
            <p class="text-lg sm:text-2xl md:text-3xl text-gray-400 mt-3 sm:mt-4">Μέσος όρος: {{ number_format($winner['average_score'], 2, ',', '') }} / 10</p>
        </div>
    @else
        <p class="text-xl sm:text-3xl md:text-4xl text-gray-500 text-center px-4">Αναμονή αποκάλυψης νικητή...</p>
    @endif
</div>
