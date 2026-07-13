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

            if (! $talentShow->show_live_scores) {
                $talentShow->update(['show_live_scores' => true]);
            }

            $this->scoreCalculationService->markTeamCompleteIfReady($team->fresh());

            return $vote;
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

    protected function validateScore(int $score): void
    {
        if ($score < 1 || $score > 10) {
            throw new InvalidArgumentException('Ο βαθμός πρέπει να είναι από 1 έως 10.');
        }
    }

    protected function assertCanVote(Judge $judge, Team $team, TalentShow $talentShow): void
    {
        if ($talentShow->showing_team_intro) {
            throw new InvalidArgumentException('Περιμένετε το τέλος του intro video πριν ψηφίσετε.');
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
}
