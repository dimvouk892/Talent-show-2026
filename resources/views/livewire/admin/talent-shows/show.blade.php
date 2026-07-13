<div>
    @include('partials.admin-show-nav', ['talentShow' => $talentShow])

    <div class="card">
        <h1 class="text-xl sm:text-2xl font-bold mb-2 break-words">{{ $talentShow->title }}</h1>
        <p class="text-gray-500 mb-4 text-sm sm:text-base">{{ $talentShow->status->label() }}</p>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div class="p-3 sm:p-4 bg-gray-50 rounded-xl text-center sm:text-left">
                <p class="text-xs sm:text-sm text-gray-500">Ομάδες</p>
                <p class="text-xl sm:text-2xl font-bold">{{ $teamsCount }}</p>
            </div>
            <div class="p-3 sm:p-4 bg-gray-50 rounded-xl text-center sm:text-left">
                <p class="text-xs sm:text-sm text-gray-500">Ενεργοί κριτές</p>
                <p class="text-xl sm:text-2xl font-bold">{{ $judgesCount }}</p>
            </div>
            <div class="p-3 sm:p-4 bg-gray-50 rounded-xl text-center sm:text-left">
                <p class="text-xs sm:text-sm text-gray-500">Συνδεδεμένοι</p>
                <p class="text-xl sm:text-2xl font-bold">{{ $connectedJudges }}</p>
            </div>
            <div class="p-3 sm:p-4 bg-gray-50 rounded-xl text-center sm:text-left">
                <p class="text-xs sm:text-sm text-gray-500">Ψήφισαν</p>
                <p class="text-xl sm:text-2xl font-bold">{{ $voteProgress['voted'] }}/{{ $voteProgress['total'] }}</p>
            </div>
        </div>

        @if ($talentShow->currentTeam)
            <div class="p-4 bg-indigo-50 rounded-xl mb-4">
                <p class="font-medium text-sm sm:text-base">Τρέχουσα ομάδα: {{ $talentShow->currentTeam->name }}</p>
            </div>
        @endif

        <div class="w-full bg-gray-200 rounded-full h-2.5 sm:h-3 mb-2">
            <div class="bg-indigo-600 h-2.5 sm:h-3 rounded-full transition-all" style="width: {{ $progress['percentage'] }}%"></div>
        </div>
        <p class="text-sm text-gray-500">Συνολική πρόοδος: {{ $progress['completed_teams'] }}/{{ $progress['total_teams'] }} ομάδες</p>
    </div>
</div>
