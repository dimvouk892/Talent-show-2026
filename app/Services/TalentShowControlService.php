<?php

namespace App\Services;

use App\Enums\TalentShowStatus;
use App\Enums\TeamStatus;
use App\Models\TalentShow;
use App\Models\Team;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteRevision;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TalentShowControlService
{
    public const MIN_JUDGES = 1;

    public function __construct(
        protected ScoreCalculationService $scoreCalculationService,
        protected ResultsService $resultsService,
        protected AuditLogService $auditLogService,
        protected JudgeAccessService $judgeAccessService,
    ) {}

    public function validateReadyToStart(TalentShow $talentShow): void
    {
        $activeJudges = $talentShow->activeJudges()->count();
        $activeTeams = $talentShow->activeTeams()->count();

        if ($activeJudges < self::MIN_JUDGES) {
            throw new InvalidArgumentException('Απαιτείται τουλάχιστον '.self::MIN_JUDGES.' ενεργός κριτής.');
        }

        if ($activeTeams < 1) {
            throw new InvalidArgumentException('Απαιτείται τουλάχιστον μία ενεργή ομάδα.');
        }
    }

    public function startShow(TalentShow $talentShow): TalentShow
    {
        $this->validateReadyToStart($talentShow);

        $talentShow->update(array_merge([
            'status' => TalentShowStatus::Ready,
            'showing_opening_video' => $talentShow->hasOpeningVideo(),
        ], $this->waitingScreenFlags($talentShow, suppressWhileOpening: true)));

        $this->auditLogService->log(
            action: 'show_started',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function openScoring(TalentShow $talentShow): TalentShow
    {
        $this->validateReadyToStart($talentShow);

        return DB::transaction(function () use ($talentShow) {
            $talentShow = TalentShow::where('id', $talentShow->id)->lockForUpdate()->first();

            $firstTeam = $talentShow->activeTeams()->ordered()->first();

            if (! $firstTeam) {
                throw new InvalidArgumentException('Δεν υπάρχει ενεργή ομάδα.');
            }

            $this->deactivateAllTeams($talentShow);

            $firstTeam->update(['status' => TeamStatus::Active]);

            $talentShow->update([
                'status' => TalentShowStatus::ScoringOpen,
                'current_team_id' => $firstTeam->id,
                'show_live_scores' => false,
                'showing_opening_video' => false,
                'showing_closing_video' => false,
                'showing_waiting_video' => false,
                'showing_waiting_image' => false,
                'showing_team_intro' => $this->shouldShowTeamIntro($firstTeam),
            ]);

            $this->auditLogService->log(
                action: 'scoring_opened',
                entityType: 'talent_show',
                entityId: $talentShow->id,
                newValues: ['current_team_id' => $firstTeam->id],
            );

            return $talentShow->fresh(['currentTeam']);
        });
    }

    public function revealScores(TalentShow $talentShow): TalentShow
    {
        $team = $talentShow->currentTeam;

        if (! $team) {
            throw new InvalidArgumentException('Δεν υπάρχει ενεργή ομάδα.');
        }

        $scores = $this->scoreCalculationService->forTeam($team);

        if ($scores['votes_count'] === 0) {
            throw new InvalidArgumentException('Δεν υπάρχουν ψήφοι ακόμα.');
        }

        $talentShow->update(['show_live_scores' => true]);
        $team->update(['score_revealed_at' => now()]);

        $this->auditLogService->log(
            action: 'scores_revealed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: [
                'team_id' => $team->id,
                'votes_count' => $scores['votes_count'],
                'is_complete' => $scores['is_complete'],
            ],
        );

        return $talentShow->fresh();
    }

    public function hideScores(TalentShow $talentShow): TalentShow
    {
        $talentShow->update(['show_live_scores' => false]);

        $this->auditLogService->log(
            action: 'scores_hidden',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function nextTeam(TalentShow $talentShow): TalentShow
    {
        return DB::transaction(function () use ($talentShow) {
            $talentShow = TalentShow::where('id', $talentShow->id)->lockForUpdate()->first();
            $currentTeam = $talentShow->currentTeam;

            if (! $currentTeam) {
                throw new InvalidArgumentException('Δεν υπάρχει ενεργή ομάδα.');
            }

            $scores = $this->scoreCalculationService->forTeam($currentTeam, $talentShow);

            if (! $scores['is_complete']) {
                throw new InvalidArgumentException('Δεν έχουν ψηφίσει όλοι οι κριτές.');
            }

            $currentTeam->update(['status' => TeamStatus::Presented]);

            $nextTeam = $talentShow->activeTeams()
                ->where('display_order', '>', $currentTeam->display_order)
                ->ordered()
                ->first();

            if (! $nextTeam) {
                return $this->completeLastTeam($talentShow, $currentTeam);
            }

            $this->deactivateAllTeams($talentShow);

            $nextTeam->update(['status' => TeamStatus::Active]);

            $talentShow->update([
                'current_team_id' => $nextTeam->id,
                'show_live_scores' => false,
                'showing_team_intro' => $this->shouldShowTeamIntro($nextTeam),
            ]);

            $this->auditLogService->log(
                action: 'team_changed',
                entityType: 'talent_show',
                entityId: $talentShow->id,
                oldValues: ['current_team_id' => $currentTeam->id],
                newValues: ['current_team_id' => $nextTeam->id],
            );

            return $talentShow->fresh(['currentTeam']);
        });
    }

    protected function completeLastTeam(TalentShow $talentShow, Team $currentTeam): TalentShow
    {
        $talentShow->update([
            'current_team_id' => null,
            'show_live_scores' => false,
            'showing_team_intro' => false,
            'status' => TalentShowStatus::ScoringClosed,
        ]);

        $this->auditLogService->log(
            action: 'last_team_completed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: ['last_team_id' => $currentTeam->id],
        );

        return $talentShow->fresh();
    }

    public function closeScoring(TalentShow $talentShow): TalentShow
    {
        $talentShow->update([
            'status' => TalentShowStatus::ScoringClosed,
            'current_team_id' => null,
            'show_live_scores' => false,
            'showing_team_intro' => false,
        ]);

        $this->deactivateAllTeams($talentShow);

        $this->auditLogService->log(
            action: 'scoring_closed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function showRanking(TalentShow $talentShow): TalentShow
    {
        $talentShow->update([
            'show_ranking' => true,
            'status' => TalentShowStatus::ResultsReady,
        ]);

        $this->auditLogService->log(
            action: 'ranking_shown',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function revealWinner(TalentShow $talentShow, ?int $teamId = null): TalentShow
    {
        $ranking = $this->resultsService->getRanking($talentShow);

        if (empty($ranking)) {
            throw new InvalidArgumentException('Δεν υπάρχουν αποτελέσματα.');
        }

        $winnerId = $teamId;

        if (! $winnerId) {
            $candidates = $this->resultsService->getWinnerCandidates($talentShow);

            if (count($candidates) > 1) {
                throw new InvalidArgumentException('Υπάρχει ισοβαθμία. Επιλέξτε χειροκίνητα τον νικητή.');
            }

            $winnerId = $candidates[0]['team']->id;
        }

        $talentShow->update([
            'winner_team_id' => $winnerId,
            'winner_revealed' => true,
            'status' => TalentShowStatus::WinnerRevealed,
        ]);

        $this->auditLogService->log(
            action: 'winner_revealed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: ['winner_team_id' => $winnerId],
        );

        return $talentShow->fresh(['winnerTeam']);
    }

    public function completeShow(TalentShow $talentShow): TalentShow
    {
        $talentShow->update([
            'status' => TalentShowStatus::Completed,
            'showing_closing_video' => $talentShow->hasClosingVideo(),
        ]);

        $this->auditLogService->log(
            action: 'show_completed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: ['showing_closing_video' => $talentShow->hasClosingVideo()],
        );

        return $talentShow->fresh();
    }

    public function archiveShow(TalentShow $talentShow): TalentShow
    {
        $talentShow->update(['status' => TalentShowStatus::Archived]);

        $this->judgeAccessService->revokeAllSessionsForTalentShow($talentShow);

        $this->auditLogService->log(
            action: 'show_archived',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function restartShow(TalentShow $talentShow): TalentShow
    {
        if ($talentShow->status === TalentShowStatus::Archived) {
            throw new InvalidArgumentException('Δεν μπορείτε να επανεκκινήσετε αρχειοθετημένη εκδήλωση.');
        }

        $this->validateReadyToStart($talentShow);

        $talentShow = $this->clearScoringData($talentShow, 'show_restarted');

        return $this->openScoring($talentShow);
    }

    public function clearScores(TalentShow $talentShow): TalentShow
    {
        if ($talentShow->status === TalentShowStatus::Archived) {
            throw new InvalidArgumentException('Δεν μπορείτε να καθαρίσετε βαθμολογίες αρχειοθετημένης εκδήλωσης.');
        }

        return $this->clearScoringData($talentShow);
    }

    protected function clearScoringData(TalentShow $talentShow, string $auditAction = 'scores_cleared'): TalentShow
    {
        return DB::transaction(function () use ($talentShow, $auditAction) {
            $talentShow = TalentShow::where('id', $talentShow->id)->lockForUpdate()->first();

            $voteIds = Vote::query()
                ->where('talent_show_id', $talentShow->id)
                ->pluck('id');

            if ($voteIds->isNotEmpty()) {
                VoteRevision::query()->whereIn('vote_id', $voteIds)->delete();
                Vote::query()->whereIn('id', $voteIds)->delete();
            }

            $talentShow->teams()->update([
                'status' => TeamStatus::Pending,
                'score_revealed_at' => null,
                'scoring_completed_at' => null,
            ]);

            $talentShow->update(array_merge([
                'status' => TalentShowStatus::Ready,
                'current_team_id' => null,
                'winner_team_id' => null,
                'show_live_scores' => false,
                'showing_team_intro' => false,
                'showing_opening_video' => false,
                'showing_closing_video' => false,
                'show_ranking' => false,
                'winner_revealed' => false,
            ], $this->waitingScreenFlags($talentShow)));

            $this->auditLogService->log(
                action: $auditAction,
                entityType: 'talent_show',
                entityId: $talentShow->id,
                newValues: ['votes_deleted' => $voteIds->count()],
            );

            return $talentShow->fresh();
        });
    }

    /**
     * @return array{showing_waiting_video: bool, showing_waiting_image: bool}
     */
    protected function waitingScreenFlags(TalentShow $talentShow, bool $suppressWhileOpening = false): array
    {
        if ($suppressWhileOpening && $talentShow->hasOpeningVideo()) {
            return [
                'showing_waiting_video' => false,
                'showing_waiting_image' => false,
            ];
        }

        if ($talentShow->hasWaitingVideo()) {
            return [
                'showing_waiting_video' => true,
                'showing_waiting_image' => false,
            ];
        }

        if ($talentShow->hasWaitingImage()) {
            return [
                'showing_waiting_video' => false,
                'showing_waiting_image' => true,
            ];
        }

        return [
            'showing_waiting_video' => false,
            'showing_waiting_image' => false,
        ];
    }

    protected function deactivateAllTeams(TalentShow $talentShow): void
    {
        $talentShow->teams()
            ->where('status', TeamStatus::Active)
            ->update(['status' => TeamStatus::Pending]);
    }

    public function dismissTeamIntro(TalentShow $talentShow): TalentShow
    {
        if (! $talentShow->showing_team_intro) {
            return $talentShow;
        }

        $talentShow->update(['showing_team_intro' => false]);

        $this->auditLogService->log(
            action: 'team_intro_dismissed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: ['current_team_id' => $talentShow->current_team_id],
        );

        return $talentShow->fresh(['currentTeam']);
    }

    public function replayTeamIntro(TalentShow $talentShow): TalentShow
    {
        $team = $talentShow->currentTeam;

        if (! $team) {
            throw new InvalidArgumentException('Δεν υπάρχει ενεργή ομάδα.');
        }

        if (! $team->hasIntroVideo()) {
            throw new InvalidArgumentException('Η ομάδα δεν έχει intro video.');
        }

        $talentShow->update([
            'showing_team_intro' => true,
            'showing_opening_video' => false,
            'showing_closing_video' => false,
            'showing_waiting_video' => false,
            'showing_waiting_image' => false,
        ]);

        $this->auditLogService->log(
            action: 'team_intro_replayed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: ['current_team_id' => $team->id],
        );

        return $talentShow->fresh(['currentTeam']);
    }

    protected function shouldShowTeamIntro(Team $team): bool
    {
        return $team->hasIntroVideo();
    }

    public function dismissOpeningVideo(TalentShow $talentShow): TalentShow
    {
        if (! $talentShow->showing_opening_video) {
            return $talentShow;
        }

        $talentShow->update(array_merge(
            ['showing_opening_video' => false],
            $this->waitingScreenFlags($talentShow),
        ));

        $this->auditLogService->log(
            action: 'opening_video_dismissed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function replayOpeningVideo(TalentShow $talentShow): TalentShow
    {
        if (! $talentShow->hasOpeningVideo()) {
            throw new InvalidArgumentException('Δεν υπάρχει video έναρξης.');
        }

        $talentShow->update([
            'showing_opening_video' => true,
            'showing_closing_video' => false,
            'showing_waiting_video' => false,
            'showing_waiting_image' => false,
            'showing_team_intro' => false,
        ]);

        $this->auditLogService->log(
            action: 'opening_video_replayed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function dismissClosingVideo(TalentShow $talentShow): TalentShow
    {
        if (! $talentShow->showing_closing_video) {
            return $talentShow;
        }

        $talentShow->update(['showing_closing_video' => false]);

        $this->auditLogService->log(
            action: 'closing_video_dismissed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function replayClosingVideo(TalentShow $talentShow): TalentShow
    {
        if (! $talentShow->hasClosingVideo()) {
            throw new InvalidArgumentException('Δεν υπάρχει video λήξης.');
        }

        $talentShow->update([
            'showing_closing_video' => true,
            'showing_opening_video' => false,
            'showing_waiting_video' => false,
            'showing_waiting_image' => false,
            'showing_team_intro' => false,
        ]);

        $this->auditLogService->log(
            action: 'closing_video_replayed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function dismissWaitingVideo(TalentShow $talentShow): TalentShow
    {
        if (! $talentShow->showing_waiting_video) {
            return $talentShow;
        }

        $talentShow->update(['showing_waiting_video' => false]);

        $this->auditLogService->log(
            action: 'waiting_video_dismissed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function replayWaitingVideo(TalentShow $talentShow): TalentShow
    {
        if (! $talentShow->hasWaitingVideo()) {
            throw new InvalidArgumentException('Δεν υπάρχει video αναμονής.');
        }

        $talentShow->update([
            'showing_waiting_video' => true,
            'showing_waiting_image' => false,
            'showing_opening_video' => false,
            'showing_closing_video' => false,
            'showing_team_intro' => false,
        ]);

        $this->auditLogService->log(
            action: 'waiting_video_replayed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function dismissWaitingImage(TalentShow $talentShow): TalentShow
    {
        if (! $talentShow->showing_waiting_image) {
            return $talentShow;
        }

        $talentShow->update(['showing_waiting_image' => false]);

        $this->auditLogService->log(
            action: 'waiting_image_dismissed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function showWaitingImage(TalentShow $talentShow): TalentShow
    {
        if (! $talentShow->hasWaitingImage()) {
            throw new InvalidArgumentException('Δεν υπάρχει εικόνα αναμονής.');
        }

        $talentShow->update([
            'showing_waiting_image' => true,
            'showing_waiting_video' => false,
            'showing_opening_video' => false,
            'showing_closing_video' => false,
            'showing_team_intro' => false,
        ]);

        $this->auditLogService->log(
            action: 'waiting_image_shown',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function canProceedToNext(TalentShow $talentShow): bool
    {
        if ($talentShow->status !== TalentShowStatus::ScoringOpen || $talentShow->showing_team_intro) {
            return false;
        }

        $team = $talentShow->currentTeam;

        if (! $team) {
            return false;
        }

        return $this->scoreCalculationService->forTeam($team, $talentShow)['is_complete'];
    }

    public function canStartShow(TalentShow $talentShow): bool
    {
        return in_array($talentShow->status, [TalentShowStatus::Draft, TalentShowStatus::Ready], true);
    }

    public function canOpenScoring(TalentShow $talentShow): bool
    {
        if (in_array($talentShow->status, [
            TalentShowStatus::ScoringOpen,
            TalentShowStatus::Archived,
            TalentShowStatus::Completed,
        ], true)) {
            return false;
        }

        try {
            $this->validateReadyToStart($talentShow);
        } catch (InvalidArgumentException) {
            return false;
        }

        return in_array($talentShow->status, [TalentShowStatus::Draft, TalentShowStatus::Ready], true);
    }

    public function canRevealScores(TalentShow $talentShow): bool
    {
        if ($talentShow->status !== TalentShowStatus::ScoringOpen || $talentShow->showing_team_intro) {
            return false;
        }

        $team = $talentShow->currentTeam;

        if (! $team) {
            return false;
        }

        $scores = $this->scoreCalculationService->forTeam($team, $talentShow);

        return $scores['votes_count'] > 0 && ! $talentShow->show_live_scores;
    }

    public function canHideScores(TalentShow $talentShow): bool
    {
        return $talentShow->status === TalentShowStatus::ScoringOpen && $talentShow->show_live_scores;
    }

    public function canCloseScoring(TalentShow $talentShow): bool
    {
        return $talentShow->status === TalentShowStatus::ScoringOpen;
    }

    public function canShowRanking(TalentShow $talentShow): bool
    {
        if ($talentShow->show_ranking) {
            return false;
        }

        return in_array($talentShow->status, [
            TalentShowStatus::ScoringClosed,
            TalentShowStatus::ResultsReady,
            TalentShowStatus::WinnerRevealed,
        ], true);
    }

    public function canRevealWinner(TalentShow $talentShow): bool
    {
        if ($talentShow->winner_revealed) {
            return false;
        }

        return in_array($talentShow->status, [
            TalentShowStatus::ScoringClosed,
            TalentShowStatus::ResultsReady,
            TalentShowStatus::WinnerRevealed,
        ], true);
    }

    public function canCompleteShow(TalentShow $talentShow): bool
    {
        return ! in_array($talentShow->status, [
            TalentShowStatus::Completed,
            TalentShowStatus::Archived,
            TalentShowStatus::Draft,
        ], true);
    }

    public function flowHint(TalentShow $talentShow): string
    {
        if ($talentShow->showing_team_intro && $talentShow->currentTeam) {
            return 'Παίζει intro της ομάδας «'.$talentShow->currentTeam->name.'» — περιμένετε ή πατήστε «Έναρξη παρουσίασης».';
        }

        if ($talentShow->showing_opening_video) {
            return 'Παίζει intro εισαγωγής στην οθόνη — διαχείριση από «Videos στην οθόνη».';
        }

        return match ($talentShow->status) {
            TalentShowStatus::Draft, TalentShowStatus::Ready => $talentShow->current_team_id
                ? 'Έτοιμο για βαθμολόγηση — πατήστε «Έναρξη βαθμολόγησης».'
                : 'Πατήστε «Έναρξη Talent Show» (προαιρετικά video αναμονής από «Videos στην οθόνη»).',
            TalentShowStatus::ScoringOpen => $talentShow->currentTeam
                ? 'Οι κριτές ψηφίζουν την τρέχουσα ομάδα. Μετά «Επόμενος διαγωνιζόμενος».'
                : 'Η βαθμολόγηση είναι ανοιχτή χωρίς ενεργή ομάδα.',
            TalentShowStatus::ScoringClosed => 'Όλες οι ομάδες ολοκληρώθηκαν — «Εμφάνιση κατάταξης».',
            TalentShowStatus::ResultsReady => $talentShow->winner_revealed
                ? 'Πατήστε «Ολοκλήρωση Talent Show».'
                : 'Πατήστε «Αποκάλυψη νικητή».',
            TalentShowStatus::WinnerRevealed => 'Πατήστε «Ολοκλήρωση Talent Show».',
            TalentShowStatus::Completed => 'Η εκδήλωση ολοκληρώθηκε.',
            TalentShowStatus::Archived => 'Η εκδήλωση είναι αρχειοθετημένη.',
        };
    }
}
