<?php

namespace App\Services;

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
            $this->forgetJudgeAuth($judge->id);
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
        $this->forgetJudgeAuth($judge->id);

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

        $this->putJudgeAuth($judge, $session, $sessionToken);
        $this->configureLaravelSessionLifetime();

        return $session;
    }

    public function authenticateViaQr(string $plainToken, Request $request): Judge
    {
        $judge = $this->validateQrToken($plainToken);

        // Same judge already authenticated in this browser — reuse (no impact on other judges).
        $existing = $this->validateSessionForJudge($judge->id);
        if ($existing) {
            return $existing;
        }

        // Do not logout other judges and do not regenerate the Laravel session id:
        // regenerating would break Livewire/CSRF in other open judge tabs.
        $this->createJudgeSession($judge, $request);

        return $judge;
    }

    public function validateSession(Request $request, Judge|int|null $judge = null): ?Judge
    {
        $judgeId = $this->resolveJudgeId($request, $judge);

        if (! $judgeId) {
            return null;
        }

        return $this->validateSessionForJudge($judgeId);
    }

    public function validateSessionForJudge(int $judgeId): ?Judge
    {
        $auth = $this->getJudgeAuth($judgeId);

        if (! $auth) {
            return null;
        }

        $judge = Judge::with('talentShow')->find($judgeId);

        if (! $judge || ! $judge->is_active) {
            $this->forgetJudgeAuth($judgeId);

            return null;
        }

        if ($judge->talent_show_id !== (int) ($auth['talent_show_id'] ?? 0)) {
            $this->forgetJudgeAuth($judgeId);

            return null;
        }

        $talentShow = $judge->talentShow;

        if (! $talentShow) {
            $this->forgetJudgeAuth($judgeId);

            return null;
        }

        $judgeSession = JudgeSession::find($auth['session_id'] ?? null);

        if (! $judgeSession || $judgeSession->revoked_at !== null) {
            $this->forgetJudgeAuth($judgeId);

            return null;
        }

        if ($judgeSession->judge_id !== $judgeId) {
            $this->forgetJudgeAuth($judgeId);

            return null;
        }

        if ($judgeSession->session_token_hash !== $this->hashToken((string) ($auth['token'] ?? ''))) {
            $this->forgetJudgeAuth($judgeId);

            return null;
        }

        if ($judgeSession->expires_at->isPast()) {
            $this->forgetJudgeAuth($judgeId);

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

    public function keepAlive(Request $request, Judge|int|null $judge = null): ?Judge
    {
        return $this->validateSession($request, $judge);
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

        $this->forgetJudgeAuth($judge->id);

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

        foreach ($judgeIds as $judgeId) {
            $this->forgetJudgeAuth((int) $judgeId);
        }

        $this->auditLogService->log(
            action: 'all_judge_sessions_revoked',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: ['sessions_revoked' => $count],
            userId: auth()->id(),
        );

        return $count;
    }

    public function logout(Request $request, Judge|int|null $judge = null): void
    {
        $judgeId = $this->resolveJudgeId($request, $judge);

        if (! $judgeId) {
            return;
        }

        $auth = $this->getJudgeAuth($judgeId);
        $this->revokeSession($auth['session_id'] ?? null);
        $this->forgetJudgeAuth($judgeId);
    }

    public function authenticatedJudgeIds(): array
    {
        $bag = session('judge_auth', []);

        if (! is_array($bag)) {
            return [];
        }

        return array_map('intval', array_keys($bag));
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

    protected function putJudgeAuth(Judge $judge, JudgeSession $session, string $plainToken): void
    {
        session()->put('judge_auth.'.$judge->id, [
            'session_id' => $session->id,
            'token' => $plainToken,
            'talent_show_id' => $judge->talent_show_id,
            'authenticated_at' => now()->toIso8601String(),
        ]);

        // Legacy single-judge keys (last active) for older helpers / redirects.
        session([
            'judge_id' => $judge->id,
            'judge_session_id' => $session->id,
            'talent_show_id' => $judge->talent_show_id,
            'authenticated_at' => now()->toIso8601String(),
            'judge_session_token' => $plainToken,
        ]);
    }

    protected function getJudgeAuth(int $judgeId): ?array
    {
        $auth = session('judge_auth.'.$judgeId);

        if (is_array($auth) && ! empty($auth['session_id']) && ! empty($auth['token'])) {
            return $auth;
        }

        // Migrate legacy single-judge session into per-judge bag.
        if ((int) session('judge_id') === $judgeId && session('judge_session_id') && session('judge_session_token')) {
            $legacy = [
                'session_id' => (int) session('judge_session_id'),
                'token' => (string) session('judge_session_token'),
                'talent_show_id' => (int) session('talent_show_id'),
                'authenticated_at' => session('authenticated_at'),
            ];
            session()->put('judge_auth.'.$judgeId, $legacy);

            return $legacy;
        }

        return null;
    }

    protected function forgetJudgeAuth(int $judgeId): void
    {
        session()->forget('judge_auth.'.$judgeId);

        if ((int) session('judge_id') === $judgeId) {
            session()->forget([
                'judge_id',
                'judge_session_id',
                'talent_show_id',
                'authenticated_at',
                'judge_session_token',
            ]);

            $remaining = $this->authenticatedJudgeIds();
            if ($remaining !== []) {
                $nextId = $remaining[array_key_last($remaining)];
                $next = $this->getJudgeAuth($nextId);
                if ($next) {
                    session([
                        'judge_id' => $nextId,
                        'judge_session_id' => $next['session_id'],
                        'talent_show_id' => $next['talent_show_id'],
                        'authenticated_at' => $next['authenticated_at'] ?? null,
                        'judge_session_token' => $next['token'],
                    ]);
                }
            }
        }
    }

    protected function resolveJudgeId(Request $request, Judge|int|null $judge): ?int
    {
        if ($judge instanceof Judge) {
            return $judge->id;
        }

        if (is_int($judge)) {
            return $judge;
        }

        $routeJudge = $request->route('judge');
        if ($routeJudge instanceof Judge) {
            return $routeJudge->id;
        }

        if (is_numeric($routeJudge)) {
            return (int) $routeJudge;
        }

        $fromSession = session('judge_id');

        return $fromSession ? (int) $fromSession : null;
    }

    protected function configureLaravelSessionLifetime(): void
    {
        $minutes = config('talent-show.judge_session_lifetime_hours', 12) * 60;
        config(['session.lifetime' => max($minutes, config('session.lifetime', 120))]);
    }
}
