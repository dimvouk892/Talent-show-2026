@php
    $links = [
        ['route' => 'admin.talent-shows.show', 'label' => 'Επισκόπηση'],
        ['route' => 'admin.talent-shows.edit', 'label' => 'Ρυθμίσεις'],
        ['route' => 'admin.talent-shows.teams', 'label' => 'Ομάδες'],
        ['route' => 'admin.talent-shows.judges', 'label' => 'Κριτές'],
        ['route' => 'admin.talent-shows.live-control', 'label' => 'Ζωντανός έλεγχος'],
        ['route' => 'admin.talent-shows.results', 'label' => 'Αποτελέσματα'],
        ['route' => 'admin.talent-shows.audit-logs', 'label' => 'Ιστορικό'],
    ];
@endphp
<nav class="mb-5 sm:mb-6" aria-label="Πλοήγηση Talent Show" x-data="{ navOpen: false }">
    <button type="button"
            class="lg:hidden w-full btn-touch bg-white border border-gray-200 text-gray-800 justify-between"
            @click="navOpen = !navOpen"
            :aria-expanded="navOpen">
        <span>Μενού εκδήλωσης</span>
        <svg class="w-5 h-5 transition-transform" :class="navOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div class="mt-2 flex flex-col lg:flex-row lg:flex-wrap gap-2"
         :class="{ 'hidden lg:flex': !navOpen, 'flex': navOpen }"
         x-cloak>
        @foreach ($links as $link)
            <a href="{{ route($link['route'], $talentShow) }}"
               class="btn-touch-sm w-full lg:w-auto text-center {{ request()->routeIs($link['route']) ? 'bg-indigo-600 text-white' : 'bg-white shadow-sm border border-gray-100 hover:bg-gray-50' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
        <a href="{{ route('presentation.show') }}" target="_blank" rel="noopener"
           class="btn-touch-sm w-full lg:w-auto text-center bg-gray-800 text-white hover:bg-gray-700">
            Monitor ↗
        </a>
        <a href="{{ route('presentation.panel') }}" target="_blank" rel="noopener"
           class="btn-touch-sm w-full lg:w-auto text-center bg-indigo-600 text-white hover:bg-indigo-500 border border-indigo-500">
            Panel ↗
        </a>
    </div>
</nav>
