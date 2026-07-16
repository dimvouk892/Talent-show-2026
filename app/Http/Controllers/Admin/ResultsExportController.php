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

        $scoringJudges = $talentShow->scoringJudges()->get();
        $finalVoter = $talentShow->finalVoter();

        // Line chart: teams with scores, lowest average → highest.
        $chartItems = collect($report['ranking'])
            ->filter(function (array $item) {
                return $item['votes_count'] > 0
                    || collect($item['judge_scores'])->contains('has_voted', true);
            })
            ->sort(function (array $a, array $b) {
                return [$a['average_score'], $a['total_score'], $a['team']->display_order]
                    <=> [$b['average_score'], $b['total_score'], $b['team']->display_order];
            })
            ->values()
            ->all();

        return view('admin.results.print', [
            'talentShow' => $talentShow,
            'report' => $report,
            'winner' => $winner,
            'scoringJudges' => $scoringJudges,
            'finalVoter' => $finalVoter,
            'chartItems' => $chartItems,
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
            $header = array_merge($header, ['Σύνολο', 'Μέγιστο', 'Μ.Ο.', 'Ψήφοι', 'Κριτές', '12άρια', '10άρια', '9άρια', 'Κατάσταση']);
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
                $row[] = $item['number_of_twelves'];
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
