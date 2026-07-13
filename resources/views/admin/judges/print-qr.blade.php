<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <title>QR Code - {{ $judge->name }}</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 40px; }
        h1 { margin-bottom: 10px; }
        .badge { display: inline-block; margin-bottom: 16px; padding: 8px 16px; border: 2px solid #312e81; border-radius: 999px; font-size: 14px; font-weight: 700; color: #312e81; }
        .qr { margin: 20px auto; }
        .note { font-size: 13px; color: #4b5563; margin-top: 12px; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <h1>{{ $judge->talentShow->title }}</h1>
    <p class="badge">Προσωπικό QR — μόνο για αυτόν τον κριτή</p>
    <h2>{{ $judge->name }}</h2>
    @if ($judge->title)<p>{{ $judge->title }}</p>@endif
    <div class="qr">{!! $svg !!}</div>
    <p class="note">Μην μοιράζετε αυτό το QR με άλλον κριτή.</p>
    <button onclick="window.print()">Εκτύπωση</button>
</body>
</html>
