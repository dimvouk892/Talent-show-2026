<div wire:poll.2s class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-8 w-full max-w-4xl mx-auto overflow-x-hidden">
    <p class="text-base sm:text-2xl text-gray-400 mb-4 text-center break-words">{{ $talentShow->title }}</p>

    @if ($showRanking && count($ranking) > 0)
        <h1 class="text-2xl sm:text-4xl md:text-5xl font-bold mb-6 sm:mb-12 text-center">Τελική κατάταξη</h1>
        <div class="w-full space-y-3 sm:space-y-4">
            @foreach ($ranking as $item)
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 p-4 sm:p-6 bg-gray-900 rounded-xl sm:rounded-2xl text-lg sm:text-2xl">
                    <span class="text-2xl sm:text-4xl font-black text-indigo-400 sm:w-16">{{ $item['ranking_position'] }}η</span>
                    <span class="flex-1 font-bold break-words">{{ $item['team']->name }}</span>
                    <span class="text-indigo-300">{{ $item['total_score'] }} / {{ $item['maximum_score'] }}</span>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-xl sm:text-3xl text-gray-500 text-center px-4">Η κατάταξη δεν είναι ακόμα διαθέσιμη</p>
    @endif
</div>
