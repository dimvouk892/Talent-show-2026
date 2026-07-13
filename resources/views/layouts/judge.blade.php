<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <title>{{ $layoutJudge?->name ?? 'Κριτής' }} - Talent Show</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-900 text-white min-h-screen overflow-x-hidden">
    <div class="min-h-screen flex flex-col max-w-lg mx-auto w-full">
        <header class="relative px-4 py-3 border-b border-slate-700 safe-top shrink-0">
            <div class="pr-24 min-w-0">
                @if ($layoutTalentShow)
                    <p class="text-xs text-slate-500 truncate">{{ $layoutTalentShow->title }}</p>
                @endif
                @if ($layoutJudge)
                    <p class="font-semibold text-sm sm:text-base truncate">{{ $layoutJudge->name }}</p>
                    @if ($layoutJudge->title)
                        <p class="text-xs text-slate-400 truncate">{{ $layoutJudge->title }}</p>
                    @endif
                @endif
            </div>
            <form method="POST" action="{{ route('judge.logout') }}" class="absolute right-3 top-3">
                @csrf
                <button type="submit" class="btn-touch-sm text-slate-400 hover:text-white hover:bg-slate-800 focus-visible:ring-slate-500" aria-label="Αποσύνδεση">
                    Αποσύνδεση
                </button>
            </form>
        </header>
        <main class="flex-1 w-full px-4 py-4 sm:py-6 safe-bottom" role="main">
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-900/50 text-red-200 rounded-xl text-center text-sm sm:text-base" role="alert">{{ session('error') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
    @livewireScripts
</body>
</html>
