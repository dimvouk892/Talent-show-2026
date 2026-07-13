<?php

namespace Tests\Feature;

use App\Services\ResultsService;
use App\Services\VoteService;
use Tests\TalentShowTestCase;

class ResultsExportTest extends TalentShowTestCase
{
    public function test_results_print_page_is_accessible(): void
    {
        $this->openScoring();
        app(VoteService::class)->submit($this->show->judges()->first(), $this->show->currentTeam, 8);

        $this->actingAs($this->admin)
            ->get(route('admin.talent-shows.results.print', $this->show))
            ->assertOk()
            ->assertSee('Εκτύπωση A4')
            ->assertSee('Διαγράμματα κατάταξης')
            ->assertSee('Συνολικό σκορ ανά ομάδα')
            ->assertSee('Αναλυτικά αποτελέσματα')
            ->assertSee($this->show->currentTeam->name);
    }

    public function test_results_csv_export_is_accessible(): void
    {
        $this->openScoring();
        app(VoteService::class)->submit($this->show->judges()->first(), $this->show->currentTeam, 9);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.talent-shows.results.export', $this->show));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($this->show->currentTeam->name, $response->streamedContent());
    }

    public function test_detailed_report_includes_judge_scores(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        app(VoteService::class)->submit($judge, $this->show->currentTeam, 7);

        $report = app(ResultsService::class)->getDetailedReport($this->show->fresh());

        $this->assertEquals(1, $report['summary']['teams_with_votes']);
        $this->assertEquals(7, $report['ranking'][0]['judge_scores'][0]['score']);
    }
}
