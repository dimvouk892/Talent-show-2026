@php
    $labelWidth = 118;
    $barStart = 124;
    $barMaxWidth = 360;
    $valueGap = 8;
    $rowHeight = count($chartItems) > 10 ? 20 : 24;
    $chartPadTop = 8;
    $chartPadBottom = 8;
    $totalChartHeight = $chartPadTop + $chartPadBottom + (count($chartItems) * $rowHeight);
    $avgChartHeight = $totalChartHeight;
    $barColors = ['#4f46e5', '#6366f1', '#7c3aed', '#2563eb', '#4338ca', '#5b21b6', '#3730a3', '#1d4ed8'];
@endphp

<section class="charts-section page-break-avoid">
    <h2>Διαγράμματα κατάταξης</h2>

    <div class="chart-block">
        <h3>Συνολικό σκορ ανά ομάδα</h3>
        <svg class="chart-svg" viewBox="0 0 520 {{ $totalChartHeight }}" role="img" aria-label="Διάγραμμα συνολικού σκορ ανά ομάδα">
            @foreach ($chartItems as $index => $item)
                @php
                    $y = $chartPadTop + ($index * $rowHeight);
                    $barWidth = $maxTotalScore > 0 ? ($item['total_score'] / $maxTotalScore) * $barMaxWidth : 0;
                    $color = $barColors[$index % count($barColors)];
                    $label = \Illuminate\Support\Str::limit($item['team']->name, 22, '…');
                    $position = $item['ranking_position'] ? $item['ranking_position'].'η' : '—';
                @endphp
                <text x="0" y="{{ $y + 14 }}" class="chart-label">{{ $position }} {{ $label }}</text>
                <rect x="{{ $barStart }}" y="{{ $y + 2 }}" width="{{ max(2, $barWidth) }}" height="{{ $rowHeight - 6 }}" rx="3" fill="{{ $color }}" opacity="{{ $item['is_complete'] ? 1 : 0.55 }}"/>
                <text x="{{ $barStart + $barWidth + $valueGap }}" y="{{ $y + 14 }}" class="chart-value">{{ $item['total_score'] }}/{{ $item['maximum_score'] }}</text>
            @endforeach
        </svg>
    </div>

    <div class="chart-block">
        <h3>Μέσος όρος ανά ομάδα (0–10)</h3>
        <svg class="chart-svg" viewBox="0 0 520 {{ $avgChartHeight }}" role="img" aria-label="Διάγραμμα μέσου όρου ανά ομάδα">
            @foreach ($chartItems as $index => $item)
                @php
                    $y = $chartPadTop + ($index * $rowHeight);
                    $barWidth = ($item['average_score'] / 10) * $barMaxWidth;
                    $color = '#0d9488';
                    $label = \Illuminate\Support\Str::limit($item['team']->name, 22, '…');
                    $position = $item['ranking_position'] ? $item['ranking_position'].'η' : '—';
                @endphp
                <text x="0" y="{{ $y + 14 }}" class="chart-label">{{ $position }} {{ $label }}</text>
                <rect x="{{ $barStart }}" y="{{ $y + 2 }}" width="{{ max(2, $barWidth) }}" height="{{ $rowHeight - 6 }}" rx="3" fill="{{ $color }}" opacity="{{ $item['is_complete'] ? 1 : 0.55 }}"/>
                <text x="{{ $barStart + $barWidth + $valueGap }}" y="{{ $y + 14 }}" class="chart-value">{{ number_format($item['average_score'], 2, ',', '') }}</text>
            @endforeach
            <line x1="{{ $barStart }}" y1="{{ $avgChartHeight - 2 }}" x2="{{ $barStart + $barMaxWidth }}" y2="{{ $avgChartHeight - 2 }}" stroke="#d1d5db" stroke-width="1"/>
            <text x="{{ $barStart }}" y="{{ $avgChartHeight }}" class="chart-axis">0</text>
            <text x="{{ $barStart + $barMaxWidth - 8 }}" y="{{ $avgChartHeight }}" class="chart-axis">10</text>
        </svg>
    </div>

    @if (count($report['judges']) > 0 && count($chartItems) > 0)
        <div class="chart-block">
            <h3>Βαθμοί ανά κριτή (ανά ομάδα)</h3>
            @php
                $heatCols = count($report['judges']);
                $heatCellW = min(42, floor(360 / max(1, $heatCols)));
                $heatLabelW = 120;
                $heatWidth = $heatLabelW + ($heatCols * $heatCellW) + 20;
                $heatRowH = 22;
                $heatHeight = 28 + (count($chartItems) * $heatRowH) + 8;
            @endphp
            <svg class="chart-svg heatmap" viewBox="0 0 {{ $heatWidth }} {{ $heatHeight }}" role="img" aria-label="Πίνακας βαθμών ανά κριτή και ομάδα">
                @foreach ($report['judges'] as $jIndex => $judge)
                    <text x="{{ $heatLabelW + ($jIndex * $heatCellW) + ($heatCellW / 2) }}" y="16" class="chart-axis" text-anchor="middle">{{ \Illuminate\Support\Str::limit($judge->name, 10, '…') }}</text>
                @endforeach
                @foreach ($chartItems as $rIndex => $item)
                    @php $y = 28 + ($rIndex * $heatRowH); @endphp
                    <text x="0" y="{{ $y + 14 }}" class="chart-label">{{ \Illuminate\Support\Str::limit($item['team']->name, 18, '…') }}</text>
                    @foreach ($report['judges'] as $jIndex => $judge)
                        @php
                            $score = collect($item['judge_scores'])->firstWhere('judge_id', $judge->id);
                            $value = ($score && $score['has_voted']) ? (int) $score['score'] : null;
                            $x = $heatLabelW + ($jIndex * $heatCellW);
                            $intensity = $value ? 0.15 + (($value / 10) * 0.85) : 0.08;
                            $fill = $value ? 'rgba(79, 70, 229, '.$intensity.')' : '#f3f4f6';
                        @endphp
                        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $heatCellW - 2 }}" height="{{ $heatRowH - 4 }}" rx="2" fill="{{ $fill }}" stroke="#e5e7eb" stroke-width="0.5"/>
                        <text x="{{ $x + (($heatCellW - 2) / 2) }}" y="{{ $y + 14 }}" class="chart-heat-value" text-anchor="middle">{{ $value ?? '—' }}</text>
                    @endforeach
                @endforeach
            </svg>
        </div>
    @endif

    <p class="chart-legend">Αχνό χρώμα = μερικό αποτέλεσμα (δεν έχουν ψηφίσει όλοι οι κριτές)</p>
</section>
