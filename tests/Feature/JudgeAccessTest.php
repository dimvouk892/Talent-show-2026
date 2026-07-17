<?php

namespace Tests\Feature;

use App\Enums\TalentShowStatus;
use App\Models\AuditLog;
use App\Models\Judge;
use App\Models\JudgeSession;
use App\Models\Team;
use App\Models\Vote;
use App\Models\VoteRevision;
use App\Services\JudgeAccessService;
use App\Services\ResultsService;
use App\Services\ScoreCalculationService;
use App\Services\TalentShowControlService;
use App\Services\VoteService;
use Tests\TalentShowTestCase;

class JudgeAccessTest extends TalentShowTestCase
{
    public function test_valid_qr_token_logs_in(): void
    {
        $judge = $this->show->judges()->first();
        $token = $this->generateQrToken($judge);

        $this->get(route('judge.access', ['judge' => $judge, 'token' => $token]))
            ->assertRedirect(route('judge.vote', $judge));

        $this->assertEquals($judge->id, session('judge_id'));
        $this->assertNotEmpty(session('judge_auth.'.$judge->id));
    }

    public function test_invalid_qr_token_rejected(): void
    {
        $judge = $this->show->judges()->first();

        $this->get(route('judge.access', ['judge' => $judge, 'token' => 'invalid-token']))
            ->assertRedirect(route('judge.access.denied'));
    }

    public function test_expired_qr_token_rejected(): void
    {
        $judge = $this->show->judges()->first();
        $service = app(JudgeAccessService::class);
        $token = $service->generatePlainToken();
        $judge->update([
            'access_token_hash' => $service->hashToken($token),
            'token_expires_at' => now()->subHour(),
        ]);

        $this->get(route('judge.access', ['judge' => $judge, 'token' => $token]))
            ->assertRedirect(route('judge.access.denied'));
    }

    public function test_inactive_judge_rejected(): void
    {
        $judge = $this->show->judges()->first();
        $judge->update(['is_active' => false]);
        $token = $this->generateQrToken($judge->fresh());

        $this->get(route('judge.access', ['judge' => $judge, 'token' => $token]))
            ->assertRedirect(route('judge.access.denied'));
    }

    public function test_wrong_judge_id_in_qr_url_rejected(): void
    {
        $judge1 = $this->show->judges()->first();
        $judge2 = $this->show->judges()->skip(1)->first();
        $token = $this->generateQrToken($judge1);

        $this->get(route('judge.access', ['judge' => $judge2, 'token' => $token]))
            ->assertRedirect(route('judge.access.denied'));
    }

    public function test_revoked_judge_session_rejected(): void
    {
        $judge = $this->show->judges()->first();
        $this->loginJudge($judge);

        JudgeSession::where('judge_id', $judge->id)->update(['revoked_at' => now()]);

        $this->get($this->judgeVoteUrl($judge))
            ->assertRedirect(route('judge.access.denied'));
    }

    public function test_rescanning_same_judge_qr_keeps_session(): void
    {
        $judge = $this->show->judges()->first();
        $token = $this->generateQrToken($judge);

        $this->get(route('judge.access', ['judge' => $judge, 'token' => $token]))
            ->assertRedirect(route('judge.vote', $judge));

        $sessionId = session('judge_auth.'.$judge->id.'.session_id');

        $this->get(route('judge.access', ['judge' => $judge, 'token' => $token]))
            ->assertRedirect(route('judge.vote', $judge));

        $this->assertEquals($judge->id, session('judge_id'));
        $this->assertEquals($sessionId, session('judge_auth.'.$judge->id.'.session_id'));
    }

    public function test_multiple_judges_can_stay_logged_in_same_browser(): void
    {
        $judge1 = $this->show->judges()->first();
        $judge2 = $this->show->judges()->skip(1)->first();

        $token1 = $this->generateQrToken($judge1);
        $token2 = $this->generateQrToken($judge2);

        $this->get(route('judge.access', ['judge' => $judge1, 'token' => $token1]))
            ->assertRedirect(route('judge.vote', $judge1));

        $this->get(route('judge.access', ['judge' => $judge2, 'token' => $token2]))
            ->assertRedirect(route('judge.vote', $judge2));

        $this->assertNotEmpty(session('judge_auth.'.$judge1->id));
        $this->assertNotEmpty(session('judge_auth.'.$judge2->id));

        $this->get(route('judge.vote', $judge1))->assertOk()->assertSee($judge1->name);
        $this->get(route('judge.vote', $judge2))->assertOk()->assertSee($judge2->name);
    }

    public function test_logging_out_one_judge_keeps_other_judge_session(): void
    {
        $judge1 = $this->show->judges()->first();
        $judge2 = $this->show->judges()->skip(1)->first();

        $this->loginJudge($judge1);
        $this->loginJudge($judge2);

        $this->post(route('judge.logout', $judge1))
            ->assertRedirect(route('judge.access.denied'));

        $this->assertEmpty(session('judge_auth.'.$judge1->id));
        $this->assertNotEmpty(session('judge_auth.'.$judge2->id));

        $this->get(route('judge.vote', $judge1))->assertRedirect(route('judge.access.denied'));
        $this->get(route('judge.vote', $judge2))->assertOk()->assertSee($judge2->name);
    }

    public function test_different_judge_qr_does_not_kick_previous_judge(): void
    {
        $judge1 = $this->show->judges()->first();
        $judge2 = $this->show->judges()->skip(1)->first();

        $token1 = $this->generateQrToken($judge1);
        $token2 = $this->generateQrToken($judge2);

        $this->get(route('judge.access', ['judge' => $judge1, 'token' => $token1]))
            ->assertRedirect(route('judge.vote', $judge1));

        $session1 = session('judge_auth.'.$judge1->id.'.session_id');

        $this->get(route('judge.access', ['judge' => $judge2, 'token' => $token2]))
            ->assertRedirect(route('judge.vote', $judge2));

        $this->assertEquals($session1, session('judge_auth.'.$judge1->id.'.session_id'));
        $this->assertEquals($this->show->id, session('judge_auth.'.$judge1->id.'.talent_show_id'));
        $this->assertEquals($this->show->id, session('judge_auth.'.$judge2->id.'.talent_show_id'));
    }

    public function test_qr_regeneration_invalidates_old_token(): void
    {
        $judge = $this->show->judges()->first();
        $oldToken = $this->generateQrToken($judge);
        $service = app(JudgeAccessService::class);
        $service->generateQrToken($judge->fresh());

        $this->get(route('judge.access', ['judge' => $judge, 'token' => $oldToken]))
            ->assertRedirect(route('judge.access.denied'));
    }

    public function test_legacy_vote_url_redirects_to_personal_judge_url(): void
    {
        $judge = $this->show->judges()->first();
        $this->loginJudge($judge);

        $this->get('/judge/vote')
            ->assertRedirect(route('judge.vote', $judge));
    }

    public function test_revoking_sessions_logs_out_judge(): void
    {
        $judge = $this->show->judges()->first();
        $this->loginJudge($judge);

        app(JudgeAccessService::class)->revokeAllSessions($judge);

        $this->get($this->judgeVoteUrl($judge))
            ->assertRedirect(route('judge.access.denied'));
    }
}
