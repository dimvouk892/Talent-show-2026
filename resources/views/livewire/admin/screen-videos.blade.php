<div wire:poll.2s="pollVideoState" class="w-full max-w-4xl mx-auto">
    @include('partials.admin-show-nav', ['talentShow' => $talentShow])

    @if ($flashSuccess)
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-xl text-sm sm:text-base" role="status">{{ $flashSuccess }}</div>
    @endif
    @if ($flashError)
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-xl text-sm sm:text-base" role="alert">{{ $flashError }}</div>
    @endif

    <header class="mb-5 sm:mb-6">
        <h1 class="text-xl sm:text-2xl font-bold">Videos στην οθόνη</h1>
        <p class="text-gray-500 text-sm sm:text-base mt-1">Προβολή videos στην οθόνη παρουσίασης</p>
        <a href="{{ route('presentation.show', $talentShow) }}" target="_blank" rel="noopener"
           class="inline-block mt-3 text-sm text-indigo-600 underline">
            Άνοιγμα οθόνης παρουσίασης ↗
        </a>
    </header>

    <div class="space-y-4">
        @if ($talentShow->hasOpeningVideo())
            <div class="card">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <p class="font-medium">Intro εισαγωγής (έναρξη εκδήλωσης)</p>
                        @if ($talentShow->showing_opening_video)
                            <p class="text-sm text-amber-700 mt-1">Παίζει τώρα στην οθόνη</p>
                        @endif
                    </div>
                    @if ($talentShow->showing_opening_video)
                        <button type="button" wire:click="dismissOpeningVideo" class="w-full sm:w-auto btn-touch bg-amber-600 text-white hover:bg-amber-500">
                            Παράλειψη
                        </button>
                    @else
                        <button type="button" wire:click="replayOpeningVideo" class="w-full sm:w-auto btn-touch bg-amber-600 text-white hover:bg-amber-500">
                            ▶ Προβολή
                        </button>
                    @endif
                </div>
            </div>
        @endif

        @if ($currentTeam && $currentTeam->video_path)
            <div class="card">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <p class="font-medium">Intro ομάδας: {{ $currentTeam->name }}</p>
                        <p class="text-sm text-gray-500 mt-1">Τρέχουσα ενεργή ομάδα</p>
                        @if ($talentShow->showing_team_intro)
                            <p class="text-sm text-amber-700 mt-1">Παίζει τώρα στην οθόνη</p>
                        @endif
                    </div>
                    @if ($talentShow->showing_team_intro)
                        <button type="button" wire:click="dismissTeamIntro" class="w-full sm:w-auto btn-touch bg-amber-600 text-white hover:bg-amber-500">
                            Παράλειψη
                        </button>
                    @else
                        <button type="button" wire:click="replayTeamIntro" class="w-full sm:w-auto btn-touch bg-indigo-600 text-white hover:bg-indigo-500">
                            ▶ Προβολή
                        </button>
                    @endif
                </div>
            </div>
        @elseif ($teamsWithVideo->isNotEmpty())
            <div class="card border-dashed border-gray-200">
                <p class="text-sm text-gray-500">
                    Υπάρχουν {{ $teamsWithVideo->count() }} ομάδες με intro video.
                    Η προβολή είναι διαθέσιμη όταν η ομάδα είναι ενεργή στον ζωντανό έλεγχο.
                </p>
            </div>
        @endif

        @if ($talentShow->hasClosingVideo())
            <div class="card">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <p class="font-medium">Τελικό video (λήξη εκδήλωσης)</p>
                        @if ($talentShow->showing_closing_video)
                            <p class="text-sm text-teal-700 mt-1">Παίζει τώρα στην οθόνη</p>
                        @endif
                    </div>
                    @if ($talentShow->showing_closing_video)
                        <button type="button" wire:click="dismissClosingVideo" class="w-full sm:w-auto btn-touch bg-teal-600 text-white hover:bg-teal-500">
                            Παράλειψη
                        </button>
                    @else
                        <button type="button" wire:click="replayClosingVideo" class="w-full sm:w-auto btn-touch bg-teal-600 text-white hover:bg-teal-500">
                            ▶ Προβολή
                        </button>
                    @endif
                </div>
            </div>
        @endif

        @if ($talentShow->hasWaitingVideo())
            <div class="card">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <p class="font-medium">Video αναμονής (οθόνη έναρξης)</p>
                        <p class="text-sm text-gray-500 mt-1">Εμφανίζεται όταν δεν παίζει κάποια ομάδα</p>
                        @if ($talentShow->showing_waiting_video)
                            <p class="text-sm text-slate-700 mt-1">Παίζει τώρα στην οθόνη (loop)</p>
                        @endif
                    </div>
                    @if ($talentShow->showing_waiting_video)
                        <button type="button" wire:click="dismissWaitingVideo" class="w-full sm:w-auto btn-touch bg-slate-600 text-white hover:bg-slate-500">
                            Παράλειψη
                        </button>
                    @else
                        <button type="button" wire:click="replayWaitingVideo" class="w-full sm:w-auto btn-touch bg-slate-600 text-white hover:bg-slate-500">
                            ▶ Προβολή
                        </button>
                    @endif
                </div>
            </div>
        @endif

        @if ($talentShow->hasWaitingImage())
            <div class="card">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <p class="font-medium">Εικόνα αναμονής (οθόνη έναρξης)</p>
                        <p class="text-sm text-gray-500 mt-1">Στατική εικόνα στην οθόνη αναμονής</p>
                        @if ($talentShow->showing_waiting_image)
                            <p class="text-sm text-slate-700 mt-1">Εμφανίζεται τώρα στην οθόνη</p>
                        @endif
                    </div>
                    @if ($talentShow->showing_waiting_image)
                        <button type="button" wire:click="dismissWaitingImage" class="w-full sm:w-auto btn-touch bg-slate-600 text-white hover:bg-slate-500">
                            Παράλειψη
                        </button>
                    @else
                        <button type="button" wire:click="showWaitingImage" class="w-full sm:w-auto btn-touch bg-slate-600 text-white hover:bg-slate-500">
                            ▶ Προβολή
                        </button>
                    @endif
                </div>
            </div>
        @endif

        @if (! $talentShow->hasOpeningVideo() && ! $talentShow->hasClosingVideo() && ! $talentShow->hasWaitingVideo() && ! $talentShow->hasWaitingImage() && $teamsWithVideo->isEmpty())
            <div class="card border-dashed border-gray-200">
                <p class="text-sm text-gray-500">
                    Δεν υπάρχουν διαθέσιμα videos.
                    <a href="{{ route('admin.talent-shows.edit', $talentShow) }}" class="text-indigo-600 underline">Videos εκδήλωσης</a>
                    ·
                    <a href="{{ route('admin.talent-shows.teams', $talentShow) }}" class="text-indigo-600 underline">Videos ομάδων</a>
                </p>
            </div>
        @else
            <p class="text-xs text-gray-500">
                <a href="{{ route('admin.talent-shows.edit', $talentShow) }}" class="text-indigo-600 underline">Επεξεργασία videos εκδήλωσης</a>
                ·
                <a href="{{ route('admin.talent-shows.teams', $talentShow) }}" class="text-indigo-600 underline">Videos ομάδων</a>
            </p>
        @endif
    </div>
</div>
