<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Σύνδεση Διαχειριστή</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center overflow-x-hidden px-4 py-6 safe-top safe-bottom">
    <div class="w-full max-w-md">
        @if (session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-xl text-sm" role="alert">{{ session('error') }}</div>
        @endif
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
