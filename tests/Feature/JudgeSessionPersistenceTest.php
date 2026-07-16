<?php

namespace Tests\Feature;

use App\Enums\TalentShowStatus;
use App\Models\JudgeSession;
use App\Services\JudgeAccessService;
use App\Services\TalentShowControlService;
use Illuminate\Support\Carbon;
use Tests\TalentShowTestCase;

class JudgeSessionPersistenceTest extends TalentShowTestCase
{
    public function test_judge_session_extends_with_sliding_expiration_on_activity(): void
    {
        $judge = $this->show->judges()->first();
        $this->loginJudge($judge);

        $session = JudgeSession::where('judge_id', $judge->id)->first();
        $originalExpiry = $session->expires_at;

        Carbon::setTestNow(now()->addHours(2));

        app(JudgeAccessService::class)->keepAlive(request());

        $session->refresh();
        $this->assertTrue($session->expires_at->greaterThan($originalExpiry));

        Carbon::setTestNow();
    }

    public function test_judge_stays_logged_in_without_new_qr_after_voting(): void
    {
        $this->openScoring();
        $judge = $this->show->judges()->first();
        $this->loginJudge($judge);

        $this->get($this->judgeVoteUrl($judge))->assertOk();

        app(\App\Services\VoteService::class)->submit($judge, $this->show->currentTeam, 10);

        $this->get($this->judgeVoteUrl($judge))
            ->assertOk()
            ->assertSee('Η βαθμολογία σας καταχωρίστηκε')
            ->assertSee('δεν χρειάζεται νέο QR scan');
    }

    public function test_deactivating_judge_revokes_sessions(): void
    {
        $judge = $this->show->judges()->first();
        $this->loginJudge($judge);

        $judge->update(['is_active' => false]);
        app(JudgeAccessService::class)->revokeAllSessions($judge);

        $this->get($this->judgeVoteUrl($judge))
            ->assertRedirect(route('judge.access.denied'));
    }

    public function test_revoke_all_sessions_for_talent_show(): void
    {
        $service = app(JudgeAccessService::class);

        foreach ($this->show->judges as $judge) {
            $service->createJudgeSession($judge, request());
        }

        $this->assertEquals(5, JudgeSession::whereNull('revoked_at')->count());

        $count = $service->revokeAllSessionsForTalentShow($this->show->fresh());
        $this->assertEquals(5, $count);
        $this->assertEquals(0, JudgeSession::whereNull('revoked_at')->count());
    }

    public function test_archive_show_revokes_all_judge_sessions(): void
    {
        $judge = $this->show->judges()->first();
        $this->loginJudge($judge);

        app(TalentShowControlService::class)->archiveShow($this->show);

        $this->assertEquals(0, JudgeSession::whereNull('revoked_at')->count());
    }

    public function test_finished_show_allows_completion_message_before_logout(): void
    {
        $judge = $this->show->judges()->first();
        $this->loginJudge($judge);

        app(TalentShowControlService::class)->completeShow($this->show->fresh());

        $this->assertDatabaseHas('talent_shows', [
            'id' => $this->show->id,
            'status' => TalentShowStatus::Completed->value,
        ]);

        $this->get($this->judgeVoteUrl($judge))
            ->assertOk()
            ->assertSee('ολοκληρώθηκε', false);
    }
}
