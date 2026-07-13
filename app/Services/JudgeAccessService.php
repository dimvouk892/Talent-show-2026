<?php

namespace App\Services;

use App\Enums\TalentShowStatus;
use App\Models\Judge;
use App\Models\JudgeSession;
use App\Models\TalentShow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

class JudgeAccessService
{
    public function __construct(
        protected AuditLogService $auditLogService,
    ) {}

    public function generatePlainToken(): string
    {
        return Str::random(64);
    }

    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function generateQrToken(Judge $judge, bool $revokePreviousSessions = true): string
    {
        $plainToken = $this->generatePlainToken();
        $expiresAt = now()->addHours(config('talent-show.qr_token_lifetime_hours', 24));

        $judge->update([
            'access_token_hash' => $this->hashToken($plainToken),
            'access_token_encrypted' => $plainToken,
            'token_generated_at' => now(),
            'token_expires_at' => $expiresAt,
        ]);

        if ($revokePreviousSessions) {
            $this->revokeAllSessions($judge);
        }

        $this->auditLogService->log(
            action: 'qr_regenerated',
            entityType: 'judge',
            entityId: $judge->id,
            newValues: ['token_expires_at' => $expiresAt->toIso8601String()],
            userId: auth()->id(),
        );

        return $plainToken;
    }

    public function revokeQrToken(Judge $judge): void
    {
        $judge->update([
            'access_token_hash' => null,
            'access_token_encrypted' => null,
            'token_generated_at' => null,
            'token_expires_at' => null,
        ]);

        $this->revokeAllSessions($judge);

        $this->auditLogService->log(
            action: 'qr_revoked',
            entityType: 'judge',
            entityId: $judge->id,
            userId: auth()->id(),
        );
    }

    public function findJudgeByToken(string $plainToken): ?Judge
    {
        $hash = $this->hashToken($plainToken);

        return Judge::where('access_token_hash', $hash)->first();
    }

    public function validateQrToken(string $plainToken): Judge
    {
        $judge = $this->findJudgeByToken($plainToken);

        if (! $judge) {
            throw new InvalidArgumentException('Μη έγκυρο QR token.');
        }

        if (! $judge->is_active) {
            throw new InvalidArgumentException('Ο κριτής δεν είναι ενεργός.');
        }

        if (! $judge->token_expires_at || $judge->token_expires_at->isPast()) {
            throw new InvalidArgumentException('Το QR token έχει λήξει.');
        }

        $talentShow = $judge->talentShow;

        if ($talentShow->status->isFinished()) {
            throw new InvalidArgumentException('Το Talent Show έχει ολοκληρωθεί.');
        }

        return $judge;
    }

    public function calculateSessionExpiration(TalentShow $talentShow, ?Carbon $from = null): Carbon
    {
        $from ??= now();
        $slidingHours = config('talent-show.judge_session_sliding_hours', 12);
        $maxHours = config('talent-show.judge_session_lifetime_hours', 12);

        $slidingExpiry = $from->copy()->addHours($slidingHours);
        $maxExpiry = $from->copy()->addHours($maxHours);

        if ($talentShow->event_date) {
            $eventEnd = $talentShow->event_date->copy()->endOfDay()->addHours($slidingHours);
            $maxExpiry = $maxExpiry->min($eventEnd);
            $slidingExpiry = $slidingExpiry->min($eventEnd);
        }

        return $slidingExpiry->min($maxExpiry);
    }

    public function createJudgeSession(Judge $judge, Request $request): JudgeSession
    {
        $sessionToken = Str::random(64);
        $talentShow = $judge->talentShow;
        $expiresAt = $this->calculateSessionExpiration($talentShow);

        $session = JudgeSession::create([
            'judge_id' => $judge->id,
            'session_token_hash' => $this->hashToken($sessionToken),
            'ip_hash' => $this->auditLogService->hashIp($request->ip()),
            'user_agent_hash' => $this->auditLogService->hashUserAgent($request->userAgent()),
            'last_activity_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        $judge->update(['last_access_at' => now()]);

        session()->put('judge_session_token', $sessionToken);

        $this->configureLaravelSessionLifetime();

        return $session;
    }

    public function authenticateViaQr(string $plainToken, Request $request): Judge
    {
        $judge = $this->validateQrToken($plainToken);
        $currentJudgeId = session('judge_id');

        if ($currentJudgeId && (int) $currentJudgeId === $judge->id) {
            $existing = $this->validateSession($request);

            if ($existing) {
                return $existing;
            }
        }

        if ($currentJudgeId && (int) $currentJudgeId !== $judge->id) {
            $this->logout($request);
        }

        $request->session()->regenerate();

        $session = $this->createJudgeSession($judge, $request);

        session([
            'judge_id' => $judge->id,
            'judge_session_id' => $session->id,
            'talent_show_id' => $judge->talent_show_id,
            'authenticated_at' => now()->toIso8601String(),
        ]);

        return $judge;
    }

    public function validateSession(Request $request): ?Judge
    {
        $judgeId = session('judge_id');
        $sessionId = session('judge_session_id');
        $talentShowId = session('talent_show_id');
        $sessionToken = session('judge_session_token');

        if (! $judgeId || ! $sessionId || ! $talentShowId || ! $sessionToken) {
            return null;
        }

        $judge = Judge::with('talentShow')->find($judgeId);

        if (! $judge || ! $judge->is_active) {
            return null;
        }

        if ($judge->talent_show_id !== (int) $talentShowId) {
            return null;
        }

        $talentShow = $judge->talentShow;

        if (! $talentShow) {
            return null;
        }

        $judgeSession = JudgeSession::find($sessionId);

        if (! $judgeSession || $judgeSession->revoked_at !== null) {
            return null;
        }

        if ($judgeSession->session_token_hash !== $this->hashToken($sessionToken)) {
            return null;
        }

        if ($judgeSession->expires_at->isPast()) {
            return null;
        }

        if ($talentShow->status->isFinished()) {
            return $judge;
        }

        $this->extendSessionSliding($judgeSession, $talentShow);
        $judge->update(['last_access_at' => now()]);

        return $judge;
    }

    public function extendSessionSliding(JudgeSession $judgeSession, TalentShow $talentShow): void
    {
        $newExpiry = $this->calculateSessionExpiration($talentShow);

        $judgeSession->update([
            'last_activity_at' => now(),
            'expires_at' => $newExpiry,
        ]);
    }

    public function keepAlive(Request $request): ?Judge
    {
        return $this->validateSession($request);
    }

    public function revokeSession(?int $sessionId): void
    {
        if (! $sessionId) {
            return;
        }

        JudgeSession::where('id', $sessionId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeAllSessions(Judge $judge): void
    {
        JudgeSession::where('judge_id', $judge->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $this->auditLogService->log(
            action: 'judge_sessions_revoked',
            entityType: 'judge',
            entityId: $judge->id,
            userId: auth()->id(),
        );
    }

    public function revokeAllSessionsForTalentShow(TalentShow $talentShow): int
    {
        $judgeIds = $talentShow->judges()->pluck('id');

        $count = JudgeSession::whereIn('judge_id', $judgeIds)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $this->auditLogService->log(
            action: 'all_judge_sessions_revoked',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: ['sessions_revoked' => $count],
            userId: auth()->id(),
        );

        return $count;
    }

    public function logout(Request $request): void
    {
        $this->revokeSession(session('judge_session_id'));

        $request->session()->forget([
            'judge_id',
            'judge_session_id',
            'talent_show_id',
            'authenticated_at',
            'judge_session_token',
        ]);

        $request->session()->regenerate();
    }

    public function getConnectedJudgesCount(TalentShow $talentShow): int
    {
        if ($talentShow->status->isFinished()) {
            return 0;
        }

        return JudgeSession::query()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->whereIn('judge_id', $talentShow->judges()->pluck('id'))
            ->distinct('judge_id')
            ->count('judge_id');
    }

    protected function configureLaravelSessionLifetime(): void
    {
        $minutes = config('talent-show.judge_session_lifetime_hours', 12) * 60;
        config(['session.lifetime' => max($minutes, config('session.lifetime', 120))]);
    }
}
