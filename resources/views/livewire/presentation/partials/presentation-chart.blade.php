@php
    $points = collect($chartItems)->values();
    $count = $points->count();
    $padLeft = 72;
    $padRight = 72;
    $padTop = 40;
    $padBottom = 90;
    $plotWidth = 720;
    $plotHeight = 200;
    $innerPad = 48; // κενό αριστερά/δεξιά μέσα στο plot για να φαίνονται τα νούμερα
    $svgWidth = $padLeft + $plotWidth + $padRight;
    $svgHeight = $padTop + $plotHeight + $padBottom;

    $scoreMin = $count > 0 ? (float) $points->min('total_score') : 0;
    $scoreMax = $count > 0 ? (float) $points->max('total_score') : 12;
    $yMin = max(0, floor($scoreMin) - 2);
    $yMax = max($yMin + 1, ceil($scoreMax) + 2);
    $yRange = $yMax - $yMin;

    $winnerTeamId = $winnerTeamId
        ?? ($winner['team']->id ?? null);

    if ($winnerTeamId === null && $count > 0) {
        $winnerTeamId = $points->sortByDesc('total_score')->first()['team']->id;
    }

    $coords = $points->map(function (array $item, int $index) use ($count, $padLeft, $padTop, $plotWidth, $plotHeight, $innerPad, $yMin, $yMax, $yRange, $winnerTeamId) {
        $usableWidth = $plotWidth - (2 * $innerPad);
        $x = $count === 1
            ? $padLeft + ($plotWidth / 2)
            : $padLeft + $innerPad + ($index / ($count - 1)) * $usableWidth;
        $total = (float) $item['total_score'];
        $clamped = max($yMin, min($yMax, $total));
        $y = $padTop + $plotHeight - (($clamped - $yMin) / $yRange) * $plotHeight;

        return [
            'x' => $x,
            'y' => $y,
            'total' => $total,
            'label' => $item['team']->name,
            'is_winner' => $item['team']->id === $winnerTeamId,
            'is_first' => $index === 0,
            'is_last' => $index === $count - 1,
        ];
    });

    $polyline = $coords->map(fn (array $p) => round($p['x'], 1).','.round($p['y'], 1))->implode(' ');
    $tickCount = 5;
@endphp

<section class="w-full mt-6 sm:mt-10 px-2 sm:px-4" aria-label="Γράφημα συνόλου">
    <h2 class="text-xl sm:text-3xl font-bold text-center mb-2 sm:mb-4">Γράφημα αποτελεσμάτων</h2>
    <p class="text-sm sm:text-base text-gray-400 text-center mb-4">Σύνολο ψήφων ανά ομάδα · αστέρι = νικήτρια</p>

    <div class="w-full overflow-x-auto rounded-2xl bg-black/40 border border-white/10 p-4 sm:p-6">
        <svg class="w-full min-w-[720px] h-auto" viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" role="img" aria-label="Γραμμικό διάγραμμα συνόλου">
            @for ($i = 0; $i <= $tickCount; $i++)
                @php
                    $value = $yMin + ($yRange * $i / $tickCount);
                    $y = $padTop + $plotHeight - (($value - $yMin) / $yRange) * $plotHeight;
                @endphp
                <line x1="{{ $padLeft }}" y1="{{ $y }}" x2="{{ $padLeft + $plotWidth }}" y2="{{ $y }}" stroke="#374151" stroke-width="1"/>
                <text x="{{ $padLeft - 12 }}" y="{{ $y + 4 }}" text-anchor="end" fill="#ffffff" font-size="14" font-weight="700">{{ number_format($value, 0, ',', '') }}</text>
            @endfor

            <line x1="{{ $padLeft }}" y1="{{ $padTop }}" x2="{{ $padLeft }}" y2="{{ $padTop + $plotHeight }}" stroke="#6b7280" stroke-width="1.5"/>
            <line x1="{{ $padLeft }}" y1="{{ $padTop + $plotHeight }}" x2="{{ $padLeft + $plotWidth }}" y2="{{ $padTop + $plotHeight }}" stroke="#6b7280" stroke-width="1.5"/>

            @if ($count > 0)
                <polyline points="{{ $polyline }}" fill="none" stroke="#818cf8" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"/>

                @foreach ($coords as $point)
                    @if ($point['is_winner'])
                        <g transform="translate({{ $point['x'] }}, {{ $point['y'] }})">
                            <path d="M0,-12 L3,-3.6 L12,-3.6 L4.8,1.6 L7.2,10 L0,4.6 L-7.2,10 L-4.8,1.6 L-12,-3.6 L-3,-3.6 Z"
                                  fill="#fbbf24" stroke="#b45309" stroke-width="1"/>
                        </g>
                    @else
                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5" fill="#a5b4fc" stroke="#312e81" stroke-width="1.5"/>
                    @endif
                    <text x="{{ $point['x'] }}" y="{{ $point['y'] - 16 }}" text-anchor="middle" fill="#ffffff" font-size="15" font-weight="800">{{ number_format($point['total'], 0, ',', '') }}</text>
                    <text x="{{ $point['x'] }}" y="{{ $padTop + $plotHeight + 32 }}" text-anchor="middle" fill="#ffffff" font-size="12" font-weight="700">
                        {{ \Illuminate\Support\Str::limit($point['label'], 14) }}
                    </text>
                @endforeach
            @endif
        </svg>
    </div>
</section>
