<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Talent Show - Παρουσίαση</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-black text-white min-h-screen overflow-x-hidden">
    <div class="w-full max-w-[100vw] overflow-x-hidden">
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
