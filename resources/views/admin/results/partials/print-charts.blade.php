@php
    $points = collect($chartItems)->values();
    $count = $points->count();
    $padLeft = 48;
    $padRight = 28;
    $padTop = 28;
    $padBottom = 90;
    $plotWidth = 700;
    $plotHeight = 220;
    $svgWidth = $padLeft + $plotWidth + $padRight;
    $svgHeight = $padTop + $plotHeight + $padBottom;

    // Margin around the 9–12 score range.
    $yMin = 8.0;
    $yMax = 13.0;
    $yRange = $yMax - $yMin;

    $winnerTeamId = ($winner && ($talentShow->winner_revealed ?? false))
        ? $winner['team']->id
        : null;

    // If no revealed winner, star the highest average (last after ascending sort).
    if ($winnerTeamId === null && $count > 0) {
        $winnerTeamId = $points->sortByDesc('average_score')->first()['team']->id;
    }

    $coords = $points->map(function (array $item, int $index) use ($count, $padLeft, $padTop, $plotWidth, $plotHeight, $yMin, $yMax, $yRange, $winnerTeamId) {
        $x = $count === 1
            ? $padLeft + ($plotWidth / 2)
            : $padLeft + ($index / ($count - 1)) * $plotWidth;
        $avg = (float) $item['average_score'];
        $clamped = max($yMin, min($yMax, $avg));
        $y = $padTop + $plotHeight - (($clamped - $yMin) / $yRange) * $plotHeight;

        return [
            'x' => $x,
            'y' => $y,
            'avg' => $avg,
            'label' => $item['team']->name,
            'is_winner' => $item['team']->id === $winnerTeamId,
        ];
    });

    $polyline = $coords->map(fn (array $p) => round($p['x'], 1).','.round($p['y'], 1))->implode(' ');
    $tickCount = 5; // 8, 9, 10, 11, 12, 13
@endphp

<section class="charts-section page-break-avoid" aria-label="Γραμμικό διάγραμμα μέσου όρου">
    <h2>Γραμμικό διάγραμμα αποτελεσμάτων</h2>
    <p class="chart-subtitle">Μέσος όρος ανά ομάδα (από χαμηλότερο προς υψηλότερο) · κλίμακα 8–13</p>

    <div class="chart-block">
        <svg class="chart-svg" viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" role="img" aria-label="Γραμμικό διάγραμμα μέσου όρου ανά ομάδα">
            {{-- Y grid & labels --}}
            @for ($i = 0; $i <= $tickCount; $i++)
                @php
                    $value = $yMin + ($yRange * $i / $tickCount);
                    $y = $padTop + $plotHeight - (($value - $yMin) / $yRange) * $plotHeight;
                @endphp
                <line x1="{{ $padLeft }}" y1="{{ $y }}" x2="{{ $padLeft + $plotWidth }}" y2="{{ $y }}" class="chart-grid"/>
                <text x="{{ $padLeft - 8 }}" y="{{ $y + 3 }}" text-anchor="end" class="chart-axis">{{ number_format($value, 0, ',', '') }}</text>
            @endfor

            {{-- Axes --}}
            <line x1="{{ $padLeft }}" y1="{{ $padTop }}" x2="{{ $padLeft }}" y2="{{ $padTop + $plotHeight }}" class="chart-axis-line"/>
            <line x1="{{ $padLeft }}" y1="{{ $padTop + $plotHeight }}" x2="{{ $padLeft + $plotWidth }}" y2="{{ $padTop + $plotHeight }}" class="chart-axis-line"/>

            <text x="14" y="{{ $padTop + ($plotHeight / 2) }}" class="chart-axis-title" transform="rotate(-90 14 {{ $padTop + ($plotHeight / 2) }})">Μ.Ο.</text>

            @if ($count > 0)
                <polyline points="{{ $polyline }}" fill="none" stroke="#4f46e5" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>

                @foreach ($coords as $point)
                    @if ($point['is_winner'])
                        <g transform="translate({{ $point['x'] }}, {{ $point['y'] }})" aria-label="Νικήτρια ομάδα">
                            <path d="M0,-11 L2.8,-3.4 L11,-3.4 L4.4,1.4 L6.8,9 L0,4.2 L-6.8,9 L-4.4,1.4 L-11,-3.4 L-2.8,-3.4 Z"
                                  fill="#d97706" stroke="#92400e" stroke-width="1"/>
                        </g>
                        <text x="{{ $point['x'] + 12 }}" y="{{ $point['y'] - 10 }}" class="chart-value chart-winner-value">{{ number_format($point['avg'], 2, ',', '') }}</text>
                    @else
                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4.5" fill="#4f46e5" stroke="#fff" stroke-width="1.5"/>
                        <text x="{{ $point['x'] + 8 }}" y="{{ $point['y'] - 8 }}" class="chart-value">{{ number_format($point['avg'], 2, ',', '') }}</text>
                    @endif
                    <text x="{{ $point['x'] }}" y="{{ $padTop + $plotHeight + 10 }}" text-anchor="end" class="chart-x-label"
                          transform="rotate(-35 {{ $point['x'] }} {{ $padTop + $plotHeight + 10 }})">{{ \Illuminate\Support\Str::limit($point['label'], 22, '…') }}</text>
                @endforeach
            @endif
        </svg>
    </div>
</section>
