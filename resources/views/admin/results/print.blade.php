<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <title>Αποτελέσματα — {{ $talentShow->title }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: sans-serif;
            color: #111;
            background: #e5e7eb;
            font-size: 11pt;
            line-height: 1.35;
        }
        .actions {
            max-width: 210mm;
            margin: 16px auto 0;
            padding: 0 12px;
        }
        .actions button {
            padding: 10px 18px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 8px;
            background: #1f2937;
            color: #fff;
        }
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 16px auto 24px;
            padding: 15mm 12mm;
            background: #fff;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.12);
        }
        h1 { margin: 0 0 4px; font-size: 18pt; }
        h2 { margin: 16pt 0 8pt; font-size: 13pt; page-break-after: avoid; }
        .meta { color: #555; margin-bottom: 14pt; font-size: 9pt; }
        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8pt;
            margin-bottom: 14pt;
        }
        .summary div {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8pt;
            font-size: 8pt;
        }
        .summary strong { display: block; font-size: 14pt; margin-top: 3pt; }
        .winner {
            background: #fef9c3;
            border: 1px solid #facc15;
            border-radius: 6px;
            padding: 10pt;
            margin-bottom: 14pt;
            font-size: 10pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6pt;
            font-size: 8.5pt;
        }
        th, td {
            border: 1px solid #bbb;
            padding: 4pt 5pt;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background: #f3f4f6;
            font-size: 7.5pt;
            font-weight: 600;
        }
        td.team { text-align: left; font-weight: 600; max-width: 42mm; word-wrap: break-word; }
        td.pos { font-weight: 700; width: 12mm; }
        .partial { color: #c2410c; font-size: 7pt; }
        .footnote {
            margin-top: 10pt;
            color: #666;
            font-size: 7.5pt;
            page-break-inside: avoid;
        }
        .charts-section {
            margin: 12pt 0 16pt;
            page-break-inside: avoid;
        }
        .charts-section h2 { margin-top: 0; }
        .chart-block {
            margin-bottom: 14pt;
            page-break-inside: avoid;
        }
        .chart-block h3 {
            margin: 0 0 6pt;
            font-size: 10pt;
            color: #374151;
        }
        .chart-svg {
            width: 100%;
            height: auto;
            display: block;
        }
        .chart-label { font-size: 8pt; fill: #111; }
        .chart-value { font-size: 8pt; fill: #374151; font-weight: 600; }
        .chart-axis { font-size: 7pt; fill: #6b7280; }
        .chart-heat-value { font-size: 7.5pt; fill: #111; font-weight: 600; }
        .chart-legend {
            margin: 0;
            font-size: 7.5pt;
            color: #6b7280;
        }
        .table-section { page-break-before: auto; }
        .page-break-avoid { page-break-inside: avoid; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }

        @page {
            size: A4 portrait;
            margin: 15mm 12mm;
        }

        @media print {
            body { background: #fff; }
            .actions { display: none; }
            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Εκτύπωση A4</button>
    </div>

    <div class="sheet">
        <h1>{{ $talentShow->title }}</h1>
        <p class="meta">
            Αναλυτικά αποτελέσματα · {{ $report['summary']['generated_at']->format('d/m/Y H:i') }}
            @if ($talentShow->event_date) · {{ $talentShow->event_date->format('d/m/Y') }}@endif
            @if ($talentShow->venue) · {{ $talentShow->venue }}@endif
            · {{ $report['summary']['show_status'] }}
        </p>

        <div class="summary">
            <div>Ομάδες με ψήφους<strong>{{ $report['summary']['teams_with_votes'] }}/{{ $report['summary']['total_teams'] }}</strong></div>
            <div>Ολοκληρωμένες<strong>{{ $report['summary']['complete_teams'] }}</strong></div>
            <div>Κριτές<strong>{{ $report['summary']['active_judges'] }}</strong></div>
            <div>Σύνολο ψήφων<strong>{{ $report['summary']['total_votes'] }}</strong></div>
        </div>

        @if ($winner && $talentShow->winner_revealed)
            <div class="winner">
                <strong>Νικήτρια ομάδα:</strong> {{ $winner['team']->name }}
                — {{ $winner['total_score'] }}/{{ $winner['maximum_score'] }}
                (Μ.Ο. {{ number_format($winner['average_score'], 2, ',', '') }})
            </div>
        @endif

        @if (count($chartItems) > 0)
            @include('admin.results.partials.print-charts')
        @endif

        <div class="table-section">
        <h2>Αναλυτικός πίνακας βαθμών</h2>
        <table>
            <thead>
                <tr>
                    <th>Θέση</th>
                    <th>Ομάδα</th>
                    @foreach ($report['judges'] as $judge)
                        <th>{{ $judge->name }}</th>
                    @endforeach
                    <th>Σύνολο</th>
                    <th>Μ.Ο.</th>
                    <th>10/9</th>
                    <th>Ψήφοι</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['ranking'] as $item)
                    <tr>
                        <td class="pos">
                            @if ($item['ranking_position'])
                                {{ $item['ranking_position'] }}η
                            @else
                                <span class="partial">Μερικό</span>
                            @endif
                        </td>
                        <td class="team">{{ $item['team']->name }}</td>
                        @foreach ($report['judges'] as $judge)
                            @php $score = collect($item['judge_scores'])->firstWhere('judge_id', $judge->id); @endphp
                            <td>
                                @if ($score && $score['has_voted'])
                                    {{ $score['score'] }}@if ($score['is_admin_edited'])*@endif
                                @else
                                    —
                                @endif
                            </td>
                        @endforeach
                        <td><strong>{{ $item['total_score'] }}</strong>/{{ $item['maximum_score'] }}</td>
                        <td>{{ number_format($item['average_score'], 2, ',', '') }}</td>
                        <td>{{ $item['number_of_tens'] }}/{{ $item['number_of_nines'] }}</td>
                        <td>{{ $item['votes_count'] }}/{{ $item['active_judges_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <p class="footnote">* Διορθωμένος βαθμός από διαχειριστή · Κριτήριο ισοβαθμίας: σύνολο → 10άρια → 9άρια · Μορφή εκτύπωσης: A4</p>
    </div>
</body>
</html>
