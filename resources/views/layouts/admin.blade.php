<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ?? 'Talent Show Admin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen overflow-x-hidden" x-data="{ mobileMenuOpen: false }">
    <nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-40 safe-top" aria-label="Κύρια πλοήγηση">
        <div class="max-w-7xl mx-auto px-4 py-3 sm:py-4">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('admin.dashboard') }}" class="text-lg sm:text-xl font-bold text-indigo-600 shrink-0">
                    Talent Show
                </a>

                <div class="hidden sm:flex items-center gap-4 text-sm">
                    <span class="truncate max-w-[12rem]" aria-label="Συνδεδεδεμένος χρήστης">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="btn-touch-sm text-red-600 hover:bg-red-50 focus-visible:ring-red-500">
                            Αποσύνδεση
                        </button>
                    </form>
                </div>

                <button type="button"
                        class="sm:hidden btn-touch-sm border border-gray-200"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        :aria-expanded="mobileMenuOpen"
                        aria-controls="admin-mobile-menu"
                        aria-label="Μενού">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>

            <div id="admin-mobile-menu"
                 x-show="mobileMenuOpen"
                 x-cloak
                 x-transition
                 class="sm:hidden mt-3 pt-3 border-t border-gray-100 space-y-2">
                <p class="text-sm text-gray-600 px-1">{{ auth()->user()->name }}</p>
                <a href="{{ route('admin.dashboard') }}" class="block w-full btn-touch bg-gray-100 text-gray-800">Πίνακας Ελέγχου</a>
                <a href="{{ route('admin.talent-shows.index') }}" class="block w-full btn-touch bg-gray-100 text-gray-800">Talent Shows</a>
                <a href="{{ route('admin.talent-shows.create') }}" class="block w-full btn-touch bg-indigo-600 text-white">Νέο Talent Show</a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="w-full btn-touch text-red-600 border border-red-200 hover:bg-red-50">
                        Αποσύνδεση
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto w-full px-4 py-5 sm:py-8 safe-bottom">
        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-xl text-sm sm:text-base" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-xl text-sm sm:text-base" role="alert">{{ session('error') }}</div>
        @endif
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
