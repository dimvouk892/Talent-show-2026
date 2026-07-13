<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalentShow;
use App\Services\ResultsService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultsExportController extends Controller
{
    public function __construct(
        protected ResultsService $resultsService,
    ) {}

    public function print(TalentShow $talentShow)
    {
        $this->authorize('view', $talentShow);

        $report = $this->resultsService->getDetailedReport($talentShow);
        $winner = $this->resultsService->getWinner($talentShow);

        $chartItems = collect($report['ranking'])
            ->sortBy(fn (array $item) => $item['ranking_position'] ?? 999)
            ->values()
            ->all();

        $maxTotalScore = max(1, collect($chartItems)->max('maximum_score') ?: 1);

        return view('admin.results.print', [
            'talentShow' => $talentShow,
            'report' => $report,
            'winner' => $winner,
            'chartItems' => $chartItems,
            'maxTotalScore' => $maxTotalScore,
        ]);
    }

    public function csv(TalentShow $talentShow): StreamedResponse
    {
        $this->authorize('view', $talentShow);

        $report = $this->resultsService->getDetailedReport($talentShow);
        $filename = 'talent-show-'.$talentShow->slug.'-results-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($talentShow, $report) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Talent Show', $talentShow->title], ';');
            fputcsv($handle, ['Ημερομηνία εξαγωγής', now()->format('d/m/Y H:i')], ';');
            fputcsv($handle, ['Κατάσταση', $report['summary']['show_status']], ';');
            fputcsv($handle, [], ';');

            $header = ['Θέση', 'Ομάδα', 'Σειρά'];
            foreach ($report['judges'] as $judge) {
                $header[] = $judge->name;
            }
            $header = array_merge($header, ['Σύνολο', 'Μέγιστο', 'Μ.Ο.', 'Ψήφοι', 'Κριτές', '10άρια', '9άρια', 'Κατάσταση']);
            fputcsv($handle, $header, ';');

            foreach ($report['ranking'] as $item) {
                $position = $item['ranking_position'] ? $item['ranking_position'].'η' : 'Μερικό';
                $row = [
                    $position,
                    $item['team']->name,
                    $item['team']->display_order,
                ];

                foreach ($report['judges'] as $judge) {
                    $score = collect($item['judge_scores'])->firstWhere('judge_id', $judge->id);
                    $row[] = $score && $score['has_voted'] ? $score['score'] : '';
                }

                $row[] = $item['total_score'];
                $row[] = $item['maximum_score'];
                $row[] = number_format($item['average_score'], 2, ',', '');
                $row[] = $item['votes_count'];
                $row[] = $item['active_judges_count'];
                $row[] = $item['number_of_tens'];
                $row[] = $item['number_of_nines'];
                $row[] = $item['is_complete'] ? 'Ολοκληρωμένο' : 'Μερικό';

                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
