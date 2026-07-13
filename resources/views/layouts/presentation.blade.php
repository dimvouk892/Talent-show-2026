<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Talent Show - Παρουσίαση</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        @keyframes confetti-fall { 0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; } 100% { transform: translateY(100vh) rotate(720deg); opacity: 0; } }
        .confetti { position: fixed; width: 10px; height: 10px; animation: confetti-fall 3s linear forwards; pointer-events: none; }
    </style>
</head>
<body class="bg-black text-white min-h-screen overflow-x-hidden">
    <div class="w-full max-w-[100vw] overflow-x-hidden">
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
