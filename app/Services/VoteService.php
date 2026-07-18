<?php

namespace App\Services;

use App\Models\Judge;
use App\Models\TalentShow;
use App\Models\Team;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteRevision;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VoteService
{
    public function __construct(
        protected ScoreCalculationService $scoreCalculationService,
        protected AuditLogService $auditLogService,
    ) {}

    public function submit(Judge $judge, Team $team, int $score): Vote
    {
        $this->validateScore($score);
        $talentShow = $team->talentShow;

        $this->assertCanVote($judge, $team, $talentShow);

        return DB::transaction(function () use ($judge, $team, $talentShow, $score) {
            $team = Team::where('id', $team->id)->lockForUpdate()->first();

            $existing = Vote::where('team_id', $team->id)
                ->where('judge_id', $judge->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new InvalidArgumentException('Έχετε ήδη υποβάλει βαθμολογία για αυτή την ομάδα.');
            }

            $vote = Vote::create([
                'talent_show_id' => $talentShow->id,
                'team_id' => $team->id,
                'judge_id' => $judge->id,
                'score' => $score,
                'submitted_at' => now(),
            ]);

            $this->scoreCalculationService->markTeamCompleteIfReady($team->fresh());

            return $vote;
        });
    }

    public function submitFinalVote(Judge $judge, Team $team, ?int $score = null): Vote
    {
        $score ??= $this->finalVoteScore();
        $this->validateFinalScore($score);
        $talentShow = $team->talentShow;

        $this->assertCanCastFinalVote($judge, $team, $talentShow);

        return DB::transaction(function () use ($judge, $team, $talentShow, $score) {
            $talentShow = TalentShow::where('id', $talentShow->id)->lockForUpdate()->first();

            if ($judge->votes()->where('talent_show_id', $talentShow->id)->exists()) {
                throw new InvalidArgumentException('Έχετε ήδη υποβάλει την τελική ψήφο.');
            }

            $vote = Vote::create([
                'talent_show_id' => $talentShow->id,
                'team_id' => $team->id,
                'judge_id' => $judge->id,
                'score' => $score,
                'submitted_at' => now(),
            ]);

            $talentShow->update([
                'final_vote_open' => false,
                'final_vote_submitted_at' => now(),
            ]);

            $this->auditLogService->log(
                action: 'final_vote_submitted',
                entityType: 'talent_show',
                entityId: $talentShow->id,
                newValues: [
                    'judge_id' => $judge->id,
                    'team_id' => $team->id,
                    'score' => $score,
                ],
            );

            return $vote;
        });
    }

    public function submitOnBehalf(Judge $judge, Team $team, int $score, string $reason, User $admin): Vote
    {
        $this->validateScore($score);

        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException('Η αιτιολογία είναι υποχρεωτική (τουλάχιστον 5 χαρακτήρες).');
        }

        $talentShow = $team->talentShow;
        $this->assertCanVote($judge, $team, $talentShow);

        return DB::transaction(function () use ($judge, $team, $talentShow, $score, $reason, $admin) {
            $team = Team::where('id', $team->id)->lockForUpdate()->first();

            $existing = Vote::where('team_id', $team->id)
                ->where('judge_id', $judge->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new InvalidArgumentException('Υπάρχει ήδη βαθμολογία για αυτόν τον κριτή. Χρησιμοποιήστε διόρθωση.');
            }

            $vote = Vote::create([
                'talent_show_id' => $talentShow->id,
                'team_id' => $team->id,
                'judge_id' => $judge->id,
                'score' => $score,
                'submitted_at' => now(),
                'is_admin_edited' => true,
                'edited_by' => $admin->id,
                'edited_at' => now(),
                'edit_reason' => $reason,
            ]);

            $this->auditLogService->log(
                action: 'vote_submitted_by_admin',
                entityType: 'vote',
                entityId: $vote->id,
                newValues: [
                    'judge_id' => $judge->id,
                    'team_id' => $team->id,
                    'score' => $score,
                    'reason' => $reason,
                ],
                userId: $admin->id,
            );

            $this->scoreCalculationService->markTeamCompleteIfReady($team->fresh());

            return $vote;
        });
    }

    public function submitFinalVoteOnBehalf(Judge $judge, Team $team, int $score, string $reason, User $admin): Vote
    {
        $this->validateFinalScore($score);

        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException('Η αιτιολογία είναι υποχρεωτική (τουλάχιστον 5 χαρακτήρες).');
        }

        if (! $judge->is_final_voter || ! $judge->is_active) {
            throw new InvalidArgumentException('Μόνο ο ενεργός κριτής τελικής ψήφου μπορεί να καταχωρηθεί.');
        }

        $talentShow = $team->talentShow;

        if ($judge->talent_show_id !== $talentShow->id || $team->talent_show_id !== $talentShow->id) {
            throw new InvalidArgumentException('Η ομάδα ή ο κριτής δεν ανήκουν σε αυτή την εκδήλωση.');
        }

        if ($talentShow->status->value === 'archived') {
            throw new InvalidArgumentException('Η εκδήλωση είναι αρχειοθετημένη.');
        }

        return DB::transaction(function () use ($judge, $team, $talentShow, $score, $reason, $admin) {
            $talentShow = TalentShow::where('id', $talentShow->id)->lockForUpdate()->first();

            if ($judge->votes()->where('talent_show_id', $talentShow->id)->exists()) {
                throw new InvalidArgumentException('Υπάρχει ήδη τελική ψήφος. Χρησιμοποιήστε διόρθωση.');
            }

            $vote = Vote::create([
                'talent_show_id' => $talentShow->id,
                'team_id' => $team->id,
                'judge_id' => $judge->id,
                'score' => $score,
                'submitted_at' => now(),
                'is_admin_edited' => true,
                'edited_by' => $admin->id,
                'edited_at' => now(),
                'edit_reason' => $reason,
            ]);

            $talentShow->update([
                'final_vote_open' => false,
                'final_vote_submitted_at' => now(),
            ]);

            $this->auditLogService->log(
                action: 'final_vote_submitted_by_admin',
                entityType: 'vote',
                entityId: $vote->id,
                newValues: [
                    'judge_id' => $judge->id,
                    'team_id' => $team->id,
                    'score' => $score,
                    'reason' => $reason,
                ],
                userId: $admin->id,
            );

            return $vote;
        });
    }

    public function correctFinalVote(Vote $vote, Team $newTeam, int $newScore, string $reason, User $admin): Vote
    {
        $this->validateFinalScore($newScore);

        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException('Η αιτιολογία είναι υποχρεωτική (τουλάχιστον 5 χαρακτήρες).');
        }

        $judge = $vote->judge;

        if (! $judge?->is_final_voter) {
            throw new InvalidArgumentException('Η ψήφος δεν ανήκει σε κριτή τελικής ψήφου.');
        }

        if ($newTeam->talent_show_id !== $vote->talent_show_id) {
            throw new InvalidArgumentException('Η ομάδα δεν ανήκει σε αυτή την εκδήλωση.');
        }

        return DB::transaction(function () use ($vote, $newTeam, $newScore, $reason, $admin) {
            $vote = Vote::where('id', $vote->id)->lockForUpdate()->first();
            $oldScore = $vote->score;
            $oldTeamId = $vote->team_id;

            VoteRevision::create([
                'vote_id' => $vote->id,
                'old_score' => $oldScore,
                'new_score' => $newScore,
                'changed_by' => $admin->id,
                'reason' => $reason,
            ]);

            $vote->update([
                'team_id' => $newTeam->id,
                'score' => $newScore,
                'is_admin_edited' => true,
                'edited_by' => $admin->id,
                'edited_at' => now(),
                'edit_reason' => $reason,
            ]);

            $this->auditLogService->log(
                action: 'final_vote_corrected',
                entityType: 'vote',
                entityId: $vote->id,
                oldValues: [
                    'team_id' => $oldTeamId,
                    'score' => $oldScore,
                ],
                newValues: [
                    'team_id' => $newTeam->id,
                    'score' => $newScore,
                    'reason' => $reason,
                ],
                userId: $admin->id,
            );

            return $vote->fresh(['team', 'judge']);
        });
    }

    public function correct(Vote $vote, int $newScore, string $reason, User $admin): Vote
    {
        $this->validateScore($newScore);

        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException('Η αιτιολογία είναι υποχρεωτική (τουλάχιστον 5 χαρακτήρες).');
        }

        return DB::transaction(function () use ($vote, $newScore, $reason, $admin) {
            $vote = Vote::where('id', $vote->id)->lockForUpdate()->first();
            $oldScore = $vote->score;

            VoteRevision::create([
                'vote_id' => $vote->id,
                'old_score' => $oldScore,
                'new_score' => $newScore,
                'changed_by' => $admin->id,
                'reason' => $reason,
            ]);

            $vote->update([
                'score' => $newScore,
                'is_admin_edited' => true,
                'edited_by' => $admin->id,
                'edited_at' => now(),
                'edit_reason' => $reason,
            ]);

            $this->auditLogService->log(
                action: 'vote_corrected',
                entityType: 'vote',
                entityId: $vote->id,
                oldValues: ['score' => $oldScore],
                newValues: ['score' => $newScore, 'reason' => $reason],
                userId: $admin->id,
            );

            $this->scoreCalculationService->markTeamCompleteIfReady($vote->team->fresh());

            return $vote->fresh();
        });
    }

    /**
     * Admin can set/correct a score for any team×judge cell from the results table.
     */
    public function adminUpsertScore(Judge $judge, Team $team, int $score, string $reason, User $admin): Vote
    {
        if ($judge->is_final_voter) {
            $this->validateFinalScore($score);
        } else {
            $this->validateScore($score);
        }

        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException('Η αιτιολογία είναι υποχρεωτική (τουλάχιστον 5 χαρακτήρες).');
        }

        $talentShow = $team->talentShow;

        if ($judge->talent_show_id !== $talentShow->id || $team->talent_show_id !== $talentShow->id) {
            throw new InvalidArgumentException('Η ομάδα ή ο κριτής δεν ανήκουν σε αυτή την εκδήλωση.');
        }

        if ($talentShow->status->value === 'archived') {
            throw new InvalidArgumentException('Η εκδήλωση είναι αρχειοθετημένη.');
        }

        if (! $judge->is_active) {
            throw new InvalidArgumentException('Ο κριτής δεν είναι ενεργός.');
        }

        if (! $team->is_active) {
            throw new InvalidArgumentException('Η ομάδα δεν είναι ενεργή.');
        }

        if ($judge->is_final_voter) {
            $existing = $judge->votes()->where('talent_show_id', $talentShow->id)->first();

            if ($existing) {
                return $this->correctFinalVote($existing, $team, $score, $reason, $admin);
            }

            return $this->submitFinalVoteOnBehalf($judge, $team, $score, $reason, $admin);
        }

        $existing = Vote::where('team_id', $team->id)
            ->where('judge_id', $judge->id)
            ->first();

        if ($existing) {
            return $this->correct($existing, $score, $reason, $admin);
        }

        return DB::transaction(function () use ($judge, $team, $talentShow, $score, $reason, $admin) {
            $vote = Vote::create([
                'talent_show_id' => $talentShow->id,
                'team_id' => $team->id,
                'judge_id' => $judge->id,
                'score' => $score,
                'submitted_at' => now(),
                'is_admin_edited' => true,
                'edited_by' => $admin->id,
                'edited_at' => now(),
                'edit_reason' => $reason,
            ]);

            $this->auditLogService->log(
                action: 'vote_submitted_by_admin',
                entityType: 'vote',
                entityId: $vote->id,
                newValues: [
                    'judge_id' => $judge->id,
                    'team_id' => $team->id,
                    'score' => $score,
                    'reason' => $reason,
                ],
                userId: $admin->id,
            );

            $this->scoreCalculationService->markTeamCompleteIfReady($team->fresh());

            return $vote;
        });
    }

    public function allowedScores(): array
    {
        return array_map('intval', config('talent-show.allowed_scores', [9, 10, 12]));
    }

    public function finalVoteScore(): int
    {
        return (int) config('talent-show.final_vote_score', 11);
    }

    public function allowedFinalScores(): array
    {
        return [$this->finalVoteScore()];
    }

    protected function validateScore(int $score): void
    {
        if (! in_array($score, $this->allowedScores(), true)) {
            throw new InvalidArgumentException('Ο βαθμός πρέπει να είναι 9, 10 ή 12.');
        }
    }

    protected function validateFinalScore(int $score): void
    {
        if (! in_array($score, $this->allowedFinalScores(), true)) {
            throw new InvalidArgumentException('Η τελική ψήφος μπορεί να είναι μόνο '.$this->finalVoteScore().'.');
        }
    }

    protected function assertCanVote(Judge $judge, Team $team, TalentShow $talentShow): void
    {
        if ($judge->is_final_voter) {
            throw new InvalidArgumentException('Ο κριτής τελικής ψήφου ψηφίζει μόνο στο τέλος, για μία ομάδα.');
        }

        if (! $talentShow->allowsVoting()) {
            throw new InvalidArgumentException('Η βαθμολόγηση δεν είναι ανοιχτή.');
        }

        if ($judge->talent_show_id !== $talentShow->id) {
            throw new InvalidArgumentException('Ο κριτής δεν ανήκει σε αυτό το Talent Show.');
        }

        if (! $judge->is_active) {
            throw new InvalidArgumentException('Ο κριτής δεν είναι ενεργός.');
        }

        if ($talentShow->current_team_id !== $team->id) {
            throw new InvalidArgumentException('Η ομάδα δεν είναι η τρέχουσα ενεργή ομάδα.');
        }

        if (! $team->is_active) {
            throw new InvalidArgumentException('Η ομάδα δεν είναι ενεργή.');
        }
    }

    protected function assertCanCastFinalVote(Judge $judge, Team $team, TalentShow $talentShow): void
    {
        if (! $judge->is_final_voter) {
            throw new InvalidArgumentException('Μόνο ο κριτής τελικής ψήφου μπορεί να ψηφίσει στο τέλος.');
        }

        if (! $talentShow->final_vote_open) {
            throw new InvalidArgumentException('Η τελική ψήφος δεν είναι ανοιχτή ακόμα.');
        }

        if ($judge->talent_show_id !== $talentShow->id || $team->talent_show_id !== $talentShow->id) {
            throw new InvalidArgumentException('Η ομάδα ή ο κριτής δεν ανήκουν σε αυτή την εκδήλωση.');
        }

        if (! $judge->is_active) {
            throw new InvalidArgumentException('Ο κριτής δεν είναι ενεργός.');
        }

        if (! $team->is_active) {
            throw new InvalidArgumentException('Η ομάδα δεν είναι ενεργή.');
        }
    }
}
