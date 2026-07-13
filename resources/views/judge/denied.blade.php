<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Δεν επιτρέπεται η πρόσβαση</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-900 text-white min-h-screen flex items-center justify-center p-4 overflow-x-hidden safe-top safe-bottom">
    <div class="text-center max-w-md w-full">
        <h1 class="text-xl sm:text-2xl font-bold mb-4">Δεν επιτρέπεται η πρόσβαση</h1>
        @if (session('error'))
            <p class="text-red-400 mb-4 text-sm sm:text-base" role="alert">{{ session('error') }}</p>
        @endif
        @if (session('success'))
            <p class="text-green-400 mb-4 text-sm sm:text-base" role="status">{{ session('success') }}</p>
        @endif
        <p class="text-slate-400 text-sm sm:text-base">Σαρώστε ξανά το QR code του κριτή για σύνδεση.</p>
    </div>
</body>
</html>
