<div wire:poll.2s="keepAlive" class="w-full max-w-lg mx-auto pb-4" role="region" aria-label="Οθόνη βαθμολόγησης κριτή">
    <div wire:key="judge-scene-{{ $judgeScene ?? 'waiting' }}"
         x-data
         x-init="$nextTick(() => {
             $el.classList.add('screen-scene-enter');
             $el.addEventListener('animationend', (event) => {
                 if (event.target === $el) {
                     $el.classList.remove('screen-scene-enter');
                 }
             }, { once: true });
         })"
         class="relative w-full">
    @if ($showCompleted)
        <section class="text-center py-10 sm:py-12 px-4" aria-label="Ολοκλήρωση εκδήλωσης">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-indigo-600/20 text-indigo-300 text-4xl mb-5" aria-hidden="true">🏁</div>
            <h2 class="text-xl sm:text-2xl font-bold mb-2">Το Talent Show ολοκληρώθηκε</h2>
            <p class="text-slate-400 text-base leading-relaxed">
                Ευχαριστούμε για τη συμμετοχή σας.<br>
                Η σύνδεσή σας έχει τερματιστεί ασφαλώς.
            </p>
            <p class="text-slate-500 text-sm mt-6">Για νέα εκδήλωση, σαρώστε νέο QR code.</p>
        </section>
    @elseif ($sessionInvalid ?? false)
        <section class="text-center py-10" aria-live="polite">
            <p class="text-slate-400">Επανασύνδεση...</p>
        </section>
    @else
        @if ($teamJustChanged && $currentTeam && ! $hasVoted)
            <div class="mb-4 p-4 bg-indigo-600/20 border border-indigo-400/40 rounded-xl text-center" role="status" aria-live="polite">
                <p class="font-semibold text-indigo-200">Νέα ομάδα ενεργή!</p>
                <p class="text-sm text-slate-300 mt-1">{{ $currentTeam->name }}</p>
            </div>
        @endif

        @if ($hasVoted)
            <section class="text-center py-8 sm:py-12" aria-label="Αναμονή">
                <div class="inline-flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-green-600/20 text-green-400 text-5xl mb-5" aria-hidden="true">✓</div>
                <h2 class="text-xl sm:text-2xl font-bold mb-2">Η βαθμολογία σας καταχωρίστηκε.</h2>
                <p class="text-slate-400 text-base sm:text-lg px-2">Αναμονή για τον επόμενο διαγωνιζόμενο.</p>
                <p class="mt-6 text-lg sm:text-xl font-medium">
                    Έχουν ψηφίσει {{ $voteProgress['voted'] }} από {{ $voteProgress['total'] }} κριτές
                </p>
                <div class="mt-4 w-full max-w-xs mx-auto bg-slate-800 rounded-full h-2 overflow-hidden" aria-hidden="true">
                    <div class="bg-indigo-500 h-2 rounded-full transition-all duration-500"
                         style="width: {{ $voteProgress['total'] > 0 ? ($voteProgress['voted'] / $voteProgress['total']) * 100 : 0 }}%"></div>
                </div>
                <p class="text-slate-600 text-xs mt-4">Η σελίδα ενημερώνεται αυτόματα — δεν χρειάζεται νέο QR scan.</p>
            </section>
        @elseif ($currentTeam)
            <section aria-label="Τρέχουσα ομάδα">
                <div class="text-center mb-6 sm:mb-8">
                    <p class="text-slate-400 text-sm uppercase tracking-wide mb-3">Τρέχουσα ομάδα</p>
                    @if ($currentTeam->photo_path)
                        <img src="{{ $currentTeam->photoUrl() }}"
                             alt="Φωτογραφία ομάδας {{ $currentTeam->name }}"
                             class="screen-media w-full max-w-[220px] sm:max-w-[280px] aspect-square mx-auto rounded-2xl object-cover mb-4 shadow-lg">
                    @endif
                    <h2 class="text-2xl sm:text-3xl font-bold leading-tight px-2">{{ $currentTeam->name }}</h2>
                </div>

                @if (! $showConfirm)
                    <div class="mb-6">
                        <p class="text-center text-slate-300 text-sm sm:text-base mb-4 font-medium">Επιλέξτε βαθμό</p>
                        <div class="grid grid-cols-5 gap-3 sm:gap-4 max-w-md mx-auto px-1"
                             role="group"
                             aria-label="Βαθμοί από 1 έως 10">
                            @for ($i = 1; $i <= 10; $i++)
                                <button type="button"
                                        wire:click="selectScore({{ $i }})"
                                        aria-pressed="{{ $selectedScore === $i ? 'true' : 'false' }}"
                                        aria-label="Βαθμός {{ $i }}"
                                        class="aspect-square min-h-14 sm:min-h-16 text-xl sm:text-2xl font-bold rounded-xl transition-all duration-300 touch-manipulation
                                               focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900
                                               {{ $selectedScore === $i ? 'bg-indigo-500 ring-2 ring-indigo-300 scale-105' : 'bg-slate-700 hover:bg-slate-600 active:bg-slate-500' }}">
                                    {{ $i }}
                                </button>
                            @endfor
                        </div>
                    </div>

                    <div class="sticky bottom-0 pt-4 pb-1 safe-bottom bg-gradient-to-t from-slate-900 via-slate-900 to-transparent">
                        <button type="button"
                                wire:click="confirmSubmit"
                                @disabled(! $selectedScore)
                                class="w-full btn-touch text-lg font-bold transition-all duration-300
                                       {{ $selectedScore ? 'bg-indigo-600 hover:bg-indigo-500 text-white focus-visible:ring-indigo-400' : 'bg-slate-700 text-slate-500 cursor-not-allowed' }}"
                                aria-disabled="{{ $selectedScore ? 'false' : 'true' }}">
                            Υποβολή βαθμού
                        </button>
                    </div>

                    <p class="text-center mt-4 text-slate-500 text-sm">
                        Έχουν ψηφίσει {{ $voteProgress['voted'] }} από {{ $voteProgress['total'] }}
                    </p>
                @else
                    <div class="bg-slate-800 rounded-2xl p-5 sm:p-6 text-center" role="dialog" aria-labelledby="confirm-title">
                        <p id="confirm-title" class="text-base sm:text-lg mb-2">
                            Επιλέξατε βαθμό:
                            <span class="text-4xl sm:text-5xl font-bold text-indigo-400 block mt-2">{{ $selectedScore }}</span>
                        </p>
                        <p class="text-slate-400 text-sm sm:text-base mb-6 leading-relaxed">
                            Θέλετε να υποβάλετε οριστικά τη βαθμολογία;<br>
                            Μετά την υποβολή δεν θα μπορείτε να την αλλάξετε.
                        </p>
                        <div class="flex flex-col gap-4">
                            <button type="button" wire:click="submit" class="w-full btn-touch bg-green-600 hover:bg-green-500 text-white focus-visible:ring-green-400">
                                Ναι, υποβολή
                            </button>
                            <button type="button" wire:click="$set('showConfirm', false)" class="w-full btn-touch bg-slate-600 hover:bg-slate-500 text-white focus-visible:ring-slate-400">
                                Ακύρωση
                            </button>
                        </div>
                    </div>
                @endif
            </section>
        @else
            <section class="text-center py-10 sm:py-12 px-2" aria-label="Αναμονή έναρξης">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-800 mb-4" aria-hidden="true">
                    <svg class="w-8 h-8 text-slate-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-xl sm:text-2xl font-bold mb-2">Αναμονή έναρξης</h2>
                <p class="text-slate-400 text-base">Δεν υπάρχει ενεργή ομάδα αυτή τη στιγμή.<br>Περιμένετε οδηγίες από τον διαχειριστή.</p>
                <p class="text-slate-600 text-xs mt-4">Η σύνδεσή σας παραμένει ενεργή — δεν χρειάζεται νέο QR scan.</p>
            </section>
        @endif
    @endif
    </div>
</div>
