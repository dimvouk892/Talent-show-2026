<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <title>Αποτελέσματα — {{ $talentShow->title }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: "DejaVu Sans", "Liberation Sans", Arial, sans-serif;
            color: #111;
            background: #e5e7eb;
            font-size: 10pt;
            line-height: 1.3;
        }
        .actions {
            max-width: 297mm;
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
            width: 297mm;
            min-height: 210mm;
            margin: 16px auto 24px;
            padding: 10mm 12mm;
            background: #fff;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.12);
        }
        h1 { margin: 0 0 4px; font-size: 16pt; }
        h2 {
            margin: 12pt 0 6pt;
            font-size: 12pt;
            page-break-after: avoid;
            break-after: avoid;
        }
        .meta { color: #555; margin-bottom: 10pt; font-size: 8.5pt; }
        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6pt;
            margin-bottom: 10pt;
        }
        .summary div {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 6pt 8pt;
            font-size: 7.5pt;
        }
        .summary strong { display: block; font-size: 13pt; margin-top: 2pt; }
        .winner {
            background: #fef9c3;
            border: 1px solid #facc15;
            border-radius: 6px;
            padding: 8pt;
            margin-bottom: 10pt;
            font-size: 9.5pt;
        }
        .table-section {
            width: 100%;
        }
        table.scores {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 7.5pt;
            margin-top: 4pt;
        }
        table.scores th,
        table.scores td {
            border: 1px solid #bbb;
            padding: 3pt 2pt;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
        table.scores th {
            background: #f3f4f6;
            font-size: 6.5pt;
            font-weight: 700;
            line-height: 1.2;
        }
        table.scores th.final-voter,
        table.scores td.final-voter {
            background: #fffbeb;
        }
        table.scores th.final-total,
        table.scores td.final-total {
            background: #eef2ff;
            font-weight: 700;
        }
        table.scores col.col-rank { width: 6%; }
        table.scores col.col-team { width: 16%; }
        table.scores col.col-judge { width: auto; }
        table.scores col.col-final-voter { width: 9%; }
        table.scores col.col-final-score { width: 8%; }
        table.scores col.col-total { width: 8%; }
        table.scores col.col-avg { width: 6%; }
        td.team {
            text-align: left;
            font-weight: 600;
            padding-left: 4pt;
            padding-right: 4pt;
        }
        td.pos { font-weight: 700; }
        .partial { color: #c2410c; font-size: 6.5pt; }
        .charts-section {
            margin: 12pt 0 0;
            page-break-inside: avoid;
            break-inside: avoid;
            page-break-before: auto;
            break-before: auto;
        }
        .charts-section h2 { margin-top: 0; }
        .chart-subtitle {
            margin: 0 0 6pt;
            font-size: 8pt;
            color: #6b7280;
        }
        .chart-block {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .chart-svg {
            width: 100%;
            height: auto;
            display: block;
            max-height: 90mm;
        }
        .chart-value { font-size: 9pt; fill: #1f2937; font-weight: 700; }
        .chart-winner-value { fill: #92400e; }
        .chart-axis { font-size: 8pt; fill: #6b7280; }
        .chart-axis-title { font-size: 8pt; fill: #6b7280; text-anchor: middle; }
        .chart-axis-line { stroke: #9ca3af; stroke-width: 1; }
        .chart-grid { stroke: #e5e7eb; stroke-width: 1; }
        .chart-x-label { font-size: 7.5pt; fill: #111; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        @page {
            size: A4 landscape;
            margin: 10mm 10mm;
        }

        @media print {
            body { background: #fff; }
            .actions { display: none !important; }
            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            table.scores {
                font-size: 7pt;
            }
            table.scores th {
                font-size: 6pt;
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
            · A4 οριζόντια
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

        <div class="table-section">
            <h2>Πίνακας βαθμολογιών</h2>
            <table class="scores">
                <colgroup>
                    <col class="col-rank">
                    <col class="col-team">
                    @foreach ($scoringJudges as $judge)
                        <col class="col-judge">
                    @endforeach
                    @if ($finalVoter)
                        <col class="col-final-voter">
                    @endif
                    <col class="col-final-score">
                    <col class="col-total">
                    <col class="col-avg">
                </colgroup>
                <thead>
                    <tr>
                        <th>Κατάταξη</th>
                        <th>Ομάδα</th>
                        @foreach ($scoringJudges as $index => $judge)
                            <th title="{{ $judge->name }}">Κριτής {{ $index + 1 }}</th>
                        @endforeach
                        @if ($finalVoter)
                            <th class="final-voter" title="{{ $finalVoter->name }}">Κριτής τελικής ψήφου</th>
                        @endif
                        <th class="final-total">Τελική βαθμολογία</th>
                        <th>Σύνολο</th>
                        <th>Μ.Ο.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['ranking'] as $item)
                        @php
                            $hasAnyVote = $item['votes_count'] > 0
                                || collect($item['judge_scores'])->contains('has_voted', true);
                            $finalScoreEntry = collect($item['judge_scores'])->firstWhere('is_final_voter', true);
                        @endphp
                        <tr>
                            <td class="pos">
                                @if ($item['ranking_position'])
                                    {{ $item['ranking_position'] }}η
                                @elseif ($hasAnyVote)
                                    <span class="partial">Μερικό</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="team">{{ $item['team']->name }}</td>
                            @foreach ($scoringJudges as $judge)
                                @php $score = collect($item['judge_scores'])->firstWhere('judge_id', $judge->id); @endphp
                                <td>
                                    @if ($score && $score['has_voted'])
                                        {{ $score['score'] }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endforeach
                            @if ($finalVoter)
                                <td class="final-voter">
                                    @if ($finalScoreEntry && $finalScoreEntry['has_voted'])
                                        {{ $finalScoreEntry['score'] }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            <td class="final-total">
                                @if ($hasAnyVote)
                                    {{ $item['total_score'] }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($hasAnyVote)
                                    {{ $item['total_score'] }}/{{ $item['maximum_score'] }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($hasAnyVote)
                                    {{ number_format($item['average_score'], 2, ',', '') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if (count($chartItems) > 0)
            @include('admin.results.partials.print-charts')
        @endif
    </div>
</body>
</html>
