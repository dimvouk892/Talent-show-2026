<?php

namespace App\Services;

use App\Enums\TalentShowStatus;
use App\Enums\TeamStatus;
use App\Models\TalentShow;
use App\Models\Team;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteRevision;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        $talentShow->update([
            'status' => TalentShowStatus::Ready,
        ]);

        $this->auditLogService->log(
            action: 'show_started',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function openScoring(TalentShow $talentShow): TalentShow
    {
        if (! in_array($talentShow->status, [TalentShowStatus::Draft, TalentShowStatus::Ready], true)) {
            throw new InvalidArgumentException('Η εκδήλωση πρέπει να είναι σε αναμονή. Διαγράψτε τα σκορ από τις Ρυθμίσεις και μετά πατήστε Έναρξη.');
        }

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
        $hasFinalVoter = $talentShow->finalVoter() !== null;

        $talentShow->update([
            'current_team_id' => null,
            'show_live_scores' => false,
            'status' => TalentShowStatus::ScoringClosed,
            'final_vote_open' => $hasFinalVoter,
            'final_vote_submitted_at' => $hasFinalVoter ? null : $talentShow->final_vote_submitted_at,
        ]);

        $this->auditLogService->log(
            action: 'last_team_completed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: [
                'last_team_id' => $currentTeam->id,
                'final_vote_open' => $hasFinalVoter,
            ],
        );

        return $talentShow->fresh();
    }

    public function closeScoring(TalentShow $talentShow): TalentShow
    {
        $hasFinalVoter = $talentShow->finalVoter() !== null;

        $talentShow->update([
            'status' => TalentShowStatus::ScoringClosed,
            'current_team_id' => null,
            'show_live_scores' => false,
            'final_vote_open' => $hasFinalVoter,
            'final_vote_submitted_at' => $hasFinalVoter ? null : $talentShow->final_vote_submitted_at,
        ]);

        $this->deactivateAllTeams($talentShow);

        $this->auditLogService->log(
            action: 'scoring_closed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: ['final_vote_open' => $hasFinalVoter],
        );

        return $talentShow->fresh();
    }

    public function openFinalVote(TalentShow $talentShow): TalentShow
    {
        if ($talentShow->status !== TalentShowStatus::ScoringClosed) {
            throw new InvalidArgumentException('Η τελική ψήφος ανοίγει μετά το κλείσιμο βαθμολόγησης.');
        }

        if (! $talentShow->finalVoter()) {
            throw new InvalidArgumentException('Δεν υπάρχει κριτής τελικής ψήφου.');
        }

        if (! $talentShow->hasPendingFinalVote()) {
            throw new InvalidArgumentException('Η τελική ψήφος έχει ήδη υποβληθεί.');
        }

        $talentShow->update(['final_vote_open' => true]);

        $this->auditLogService->log(
            action: 'final_vote_opened',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function showRanking(TalentShow $talentShow): TalentShow
    {
        if ($talentShow->hasPendingFinalVote()) {
            throw new InvalidArgumentException('Περιμένετε την τελική ψήφο του ειδικού κριτή πριν τα αποτελέσματα.');
        }

        $talentShow->update([
            'show_ranking' => true,
            'status' => TalentShowStatus::ResultsReady,
            'final_vote_open' => false,
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

        $topPlaces = $this->resultsService->getTopPlaces($talentShow);
        $totalSteps = count($topPlaces);

        $talentShow->update([
            'winner_team_id' => $winnerId,
            'winner_revealed' => true,
            'status' => TalentShowStatus::WinnerRevealed,
            'podium_reveal_step' => max($totalSteps, (int) $talentShow->podium_reveal_step),
        ]);

        $this->auditLogService->log(
            action: 'winner_revealed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: ['winner_team_id' => $winnerId],
        );

        return $talentShow->fresh(['winnerTeam']);
    }

    public function startPodiumReveal(TalentShow $talentShow, ?int $teamId = null): TalentShow
    {
        if (! $this->canStartPodiumReveal($talentShow)) {
            throw new InvalidArgumentException('Δεν μπορεί να ξεκινήσει η αποκάλυψη top 5.');
        }

        $topPlaces = $this->resultsService->getTopPlaces($talentShow);

        if (empty($topPlaces)) {
            throw new InvalidArgumentException('Δεν υπάρχουν ολοκληρωμένα αποτελέσματα.');
        }

        $winnerId = $teamId ?: $talentShow->winner_team_id;
        $candidates = $this->resultsService->getWinnerCandidates($talentShow);

        if (count($candidates) > 1 && ! $winnerId) {
            throw new InvalidArgumentException('Υπάρχει ισοβαθμία. Επιλέξτε χειροκίνητα τον νικητή.');
        }

        if (! $winnerId) {
            $winnerId = $candidates[0]['team']->id;
        }

        $talentShow->update([
            'winner_team_id' => $winnerId,
            'winner_revealed' => false,
            'podium_reveal_step' => 1,
            'status' => TalentShowStatus::ResultsReady,
        ]);

        $this->auditLogService->log(
            action: 'podium_reveal_started',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: [
                'podium_reveal_step' => 1,
                'winner_team_id' => $winnerId,
            ],
        );

        if (count($topPlaces) === 1) {
            return $this->finalizePodiumWinner($talentShow->fresh());
        }

        return $talentShow->fresh(['winnerTeam']);
    }

    public function nextPodiumReveal(TalentShow $talentShow): TalentShow
    {
        if (! $this->canAdvancePodium($talentShow)) {
            throw new InvalidArgumentException('Δεν υπάρχει επόμενο βήμα αποκάλυψης.');
        }

        $state = $this->resultsService->getPodiumRevealState($talentShow);
        $nextStep = $state['step'] + 1;

        $talentShow->update(['podium_reveal_step' => $nextStep]);

        $this->auditLogService->log(
            action: 'podium_reveal_advanced',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: ['podium_reveal_step' => $nextStep],
        );

        $talentShow = $talentShow->fresh();

        if ($nextStep >= $state['total_steps']) {
            return $this->finalizePodiumWinner($talentShow);
        }

        return $talentShow;
    }

    public function previousPodiumReveal(TalentShow $talentShow): TalentShow
    {
        if (! $this->canRewindPodium($talentShow)) {
            throw new InvalidArgumentException('Δεν υπάρχει προηγούμενο βήμα αποκάλυψης.');
        }

        $nextStep = max(0, (int) $talentShow->podium_reveal_step - 1);

        $talentShow->update([
            'podium_reveal_step' => $nextStep,
            'winner_revealed' => false,
            'status' => $nextStep > 0
                ? TalentShowStatus::ResultsReady
                : ($talentShow->show_ranking ? TalentShowStatus::ResultsReady : $talentShow->status),
        ]);

        $this->auditLogService->log(
            action: 'podium_reveal_rewound',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: ['podium_reveal_step' => $nextStep],
        );

        return $talentShow->fresh();
    }

    protected function finalizePodiumWinner(TalentShow $talentShow): TalentShow
    {
        $winnerId = $talentShow->winner_team_id;

        if (! $winnerId) {
            $candidates = $this->resultsService->getWinnerCandidates($talentShow);

            if (empty($candidates)) {
                throw new InvalidArgumentException('Δεν υπάρχουν αποτελέσματα.');
            }

            if (count($candidates) > 1) {
                throw new InvalidArgumentException('Υπάρχει ισοβαθμία. Επιλέξτε χειροκίνητα τον νικητή.');
            }

            $winnerId = $candidates[0]['team']->id;
        }

        $topPlaces = $this->resultsService->getTopPlaces($talentShow);

        $talentShow->update([
            'winner_team_id' => $winnerId,
            'winner_revealed' => true,
            'status' => TalentShowStatus::WinnerRevealed,
            'podium_reveal_step' => count($topPlaces),
        ]);

        $this->auditLogService->log(
            action: 'winner_revealed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: ['winner_team_id' => $winnerId, 'via' => 'podium'],
        );

        return $talentShow->fresh(['winnerTeam']);
    }

    public function showFinalOverview(TalentShow $talentShow): TalentShow
    {
        if (! $this->canShowFinalOverview($talentShow)) {
            throw new InvalidArgumentException('Η πλήρης κατάταξη εμφανίζεται μετά την ολοκλήρωση της τελετής top 5.');
        }

        $talentShow->update(['show_final_overview' => true]);

        $this->auditLogService->log(
            action: 'final_overview_shown',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function hideFinalOverview(TalentShow $talentShow): TalentShow
    {
        $talentShow->update(['show_final_overview' => false]);

        $this->auditLogService->log(
            action: 'final_overview_hidden',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function storePresentationBackground(TalentShow $talentShow, UploadedFile $file): TalentShow
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $mime = (string) ($file->getMimeType() ?: '');

        $videoExtensions = ['mp4', 'webm', 'mov', 'm4v', 'ogg', 'ogv'];
        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        $type = null;
        if (str_starts_with($mime, 'video/') || in_array($extension, $videoExtensions, true)) {
            $type = 'video';
        } elseif (str_starts_with($mime, 'image/') || in_array($extension, $imageExtensions, true)) {
            $type = 'image';
        }

        if (! $type) {
            throw new InvalidArgumentException('Επιτρέπονται μόνο εικόνες (jpg, png, webp, gif) ή βίντεο (mp4, webm, mov).');
        }

        if ($talentShow->presentation_bg_path) {
            Storage::disk('public')->delete($talentShow->presentation_bg_path);
        }

        $path = $file->store('talent-shows/'.$talentShow->id.'/background', 'public');

        if (! $path || ! Storage::disk('public')->exists($path)) {
            throw new InvalidArgumentException('Αποτυχία αποθήκευσης αρχείου. Ελέγξτε δικαιώματα storage.');
        }

        $talentShow->update([
            'presentation_bg_path' => $path,
            'presentation_bg_type' => $type,
        ]);

        $this->auditLogService->log(
            action: 'presentation_background_updated',
            entityType: 'talent_show',
            entityId: $talentShow->id,
            newValues: ['type' => $type, 'path' => $path],
        );

        return $talentShow->fresh();
    }

    public function removePresentationBackground(TalentShow $talentShow): TalentShow
    {
        if ($talentShow->presentation_bg_path) {
            Storage::disk('public')->delete($talentShow->presentation_bg_path);
        }

        $talentShow->update([
            'presentation_bg_path' => null,
            'presentation_bg_type' => null,
        ]);

        $this->auditLogService->log(
            action: 'presentation_background_removed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
        );

        return $talentShow->fresh();
    }

    public function completeShow(TalentShow $talentShow): TalentShow
    {
        $talentShow->update([
            'status' => TalentShowStatus::Completed,
        ]);

        $this->auditLogService->log(
            action: 'show_completed',
            entityType: 'talent_show',
            entityId: $talentShow->id,
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

            $talentShow->update([
                'status' => TalentShowStatus::Ready,
                'current_team_id' => null,
                'winner_team_id' => null,
                'show_live_scores' => false,
                'final_vote_open' => false,
                'final_vote_submitted_at' => null,
                'show_ranking' => false,
                'winner_revealed' => false,
                'podium_reveal_step' => 0,
                'show_final_overview' => false,
            ]);

            $this->auditLogService->log(
                action: $auditAction,
                entityType: 'talent_show',
                entityId: $talentShow->id,
                newValues: ['votes_deleted' => $voteIds->count()],
            );

            return $talentShow->fresh();
        });
    }

    protected function deactivateAllTeams(TalentShow $talentShow): void
    {
        $talentShow->teams()
            ->where('status', TeamStatus::Active)
            ->update(['status' => TeamStatus::Pending]);
    }

    public function canProceedToNext(TalentShow $talentShow): bool
    {
        if ($talentShow->status !== TalentShowStatus::ScoringOpen) {
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
        if (! in_array($talentShow->status, [TalentShowStatus::Draft, TalentShowStatus::Ready], true)) {
            return false;
        }

        try {
            $this->validateReadyToStart($talentShow);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    public function canRevealWinner(TalentShow $talentShow): bool
    {
        if ($talentShow->winner_revealed || $talentShow->hasPendingFinalVote()) {
            return false;
        }

        if (! in_array($talentShow->status, [
            TalentShowStatus::ScoringClosed,
            TalentShowStatus::ResultsReady,
            TalentShowStatus::WinnerRevealed,
        ], true)) {
            return false;
        }

        $ranking = array_filter(
            $this->resultsService->getRanking($talentShow),
            fn (array $item) => $item['is_complete']
        );

        return count($ranking) > 0;
    }

    public function canStartPodiumReveal(TalentShow $talentShow): bool
    {
        if ($talentShow->podium_reveal_step > 0 || $talentShow->winner_revealed || $talentShow->hasPendingFinalVote()) {
            return false;
        }

        if (! $talentShow->show_ranking) {
            return false;
        }

        if (! in_array($talentShow->status, [
            TalentShowStatus::ScoringClosed,
            TalentShowStatus::ResultsReady,
        ], true)) {
            return false;
        }

        return count($this->resultsService->getTopPlaces($talentShow)) > 0;
    }

    public function canAdvancePodium(TalentShow $talentShow): bool
    {
        if ($talentShow->hasPendingFinalVote()) {
            return false;
        }

        $state = $this->resultsService->getPodiumRevealState($talentShow);

        return $state['step'] > 0 && $state['step'] < $state['total_steps'];
    }

    public function canRewindPodium(TalentShow $talentShow): bool
    {
        return (int) $talentShow->podium_reveal_step > 0;
    }

    public function canShowFinalOverview(TalentShow $talentShow): bool
    {
        if ($talentShow->show_final_overview || $talentShow->hasPendingFinalVote()) {
            return false;
        }

        $podium = $this->resultsService->getPodiumRevealState($talentShow);

        return $talentShow->winner_revealed || $podium['is_complete'];
    }

    public function canHideFinalOverview(TalentShow $talentShow): bool
    {
        return (bool) $talentShow->show_final_overview;
    }

    public function canRevealScores(TalentShow $talentShow): bool
    {
        if ($talentShow->status !== TalentShowStatus::ScoringOpen) {
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
        if ($talentShow->show_ranking || $talentShow->hasPendingFinalVote()) {
            return false;
        }

        return in_array($talentShow->status, [
            TalentShowStatus::ScoringClosed,
            TalentShowStatus::ResultsReady,
            TalentShowStatus::WinnerRevealed,
        ], true);
    }

    public function canOpenFinalVote(TalentShow $talentShow): bool
    {
        return $talentShow->status === TalentShowStatus::ScoringClosed
            && $talentShow->finalVoter() !== null
            && $talentShow->hasPendingFinalVote()
            && ! $talentShow->final_vote_open;
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
        $podium = $this->resultsService->getPodiumRevealState($talentShow);

        if ($podium['step'] > 0 && ! $podium['is_complete']) {
            $next = $podium['next'];
            $place = $next ? $next['ranking_position'].'η' : 'επόμενη';

            return "Τελετή top 5 σε εξέλιξη. Επόμενο: {$place} θέση.";
        }

        return match ($talentShow->status) {
            TalentShowStatus::Draft, TalentShowStatus::Ready => 'Πατήστε «Έναρξη ψηφοφορίας».',
            TalentShowStatus::ScoringOpen => $talentShow->currentTeam
                ? ($talentShow->show_live_scores
                    ? 'Τα σκορ φαίνονται στο monitor. Όταν ψηφίσουν όλοι → «Επόμενη ομάδα».'
                    : 'Οι κριτές ψηφίζουν. Όταν θέλετε → «Εμφάνιση σκορ στο monitor».')
                : 'Η ψηφοφορία είναι ανοιχτή χωρίς ενεργή ομάδα.',
            TalentShowStatus::ScoringClosed => $talentShow->hasPendingFinalVote()
                ? ($talentShow->final_vote_open
                    ? 'Αναμονή τελικής ψήφου από τον ειδικό κριτή.'
                    : 'Ανοίξτε την τελική ψήφο όταν είστε έτοιμοι.')
                : ($talentShow->show_ranking
                    ? 'Εμφανίστε την τελετή top 5 στο monitor νικητών.'
                    : 'Εμφανίστε την κατάταξη και μετά την τελετή top 5.'),
            TalentShowStatus::ResultsReady => $talentShow->podium_reveal_step > 0
                ? 'Η τελετή top 5 ολοκληρώθηκε ή συνεχίστε τα βήματα.'
                : 'Ξεκινήστε την αποκάλυψη top 5 (5η → 1η θέση).',
            TalentShowStatus::WinnerRevealed => $talentShow->show_final_overview
                ? 'Εμφανίζεται η πλήρης κατάταξη και το γράφημα. Μπορείτε να καθαρίσετε ή να ξεκινήσετε ξανά.'
                : 'Ο νικητής αποκαλύφθηκε. Προαιρετικά εμφανίστε όλες τις ομάδες + γράφημα.',
            TalentShowStatus::Completed => 'Η εκδήλωση ολοκληρώθηκε.',
            TalentShowStatus::Archived => 'Η εκδήλωση είναι αρχειοθετημένη.',
        };
    }
}
