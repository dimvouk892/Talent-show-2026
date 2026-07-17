<?php

namespace App\Services;

use App\Enums\TeamStatus;
use App\Models\Judge;
use App\Models\TalentShow;
use App\Models\Team;

class ScoreCalculationService
{
    public function forTeam(Team $team, ?TalentShow $talentShow = null): array
    {
        $talentShow ??= $team->talentShow;
        $scoringJudgeIds = $talentShow->scoringJudges()->pluck('id');
        $scoringJudgesCount = $scoringJudgeIds->count();
        $votes = $team->votes()->whereIn('judge_id', $scoringJudgeIds)->get();
        $votesCount = $votes->count();

        $finalVoter = $talentShow->finalVoter();
        $finalVote = $finalVoter
            ? $team->votes()->where('judge_id', $finalVoter->id)->first()
            : null;

        $maxPerVote = (int) config('talent-show.max_score', 12);
        $scoringTotal = (int) $votes->sum('score');
        $finalScore = (int) ($finalVote?->score ?? 0);
        $totalScore = $scoringTotal + $finalScore;
        // Μ.Ο. μόνο από κριτές βαθμολόγησης — η τελική ψήφος μετρά στο σύνολο, όχι στον Μ.Ο.
        $averageScore = $votesCount > 0 ? round($scoringTotal / $votesCount, 2) : 0;
        $maximumScore = ($scoringJudgesCount * $maxPerVote) + ($finalVoter ? $maxPerVote : 0);

        $allScores = $votes->pluck('score');
        if ($finalVote) {
            $allScores->push($finalVote->score);
        }

        return [
            'votes_count' => $votesCount,
            'active_judges_count' => $scoringJudgesCount,
            'total_score' => $totalScore,
            'average_score' => $averageScore,
            'maximum_score' => $maximumScore,
            'is_complete' => $votesCount >= $scoringJudgesCount && $scoringJudgesCount > 0,
            'number_of_twelves' => $allScores->filter(fn ($score) => (int) $score === 12)->count(),
            'number_of_tens' => $allScores->filter(fn ($score) => (int) $score === 10)->count(),
            'number_of_nines' => $allScores->filter(fn ($score) => (int) $score === 9)->count(),
            'has_final_vote' => $finalVote !== null,
            'final_vote_score' => $finalVote?->score,
        ];
    }

    public function votesProgress(TalentShow $talentShow, ?Team $team = null): array
    {
        $team ??= $talentShow->currentTeam;

        if (! $team) {
            return ['voted' => 0, 'total' => 0];
        }

        $activeJudgeIds = $talentShow->scoringJudges()->pluck('id');
        $total = $activeJudgeIds->count();
        $voted = $team->votes()->whereIn('judge_id', $activeJudgeIds)->count();

        return ['voted' => $voted, 'total' => $total];
    }

    public function judgeVoteStatus(TalentShow $talentShow, Team $team): array
    {
        $judges = $talentShow->scoringJudges()->get();
        $votes = $team->votes()->get()->keyBy('judge_id');

        return $judges->map(function (Judge $judge) use ($votes) {
            $vote = $votes->get($judge->id);

            return [
                'judge' => $judge,
                'judge_number' => $judge->display_order,
                'vote' => $vote,
                'has_voted' => $vote !== null,
                'score' => $vote?->score,
            ];
        })->all();
    }

    public function markTeamCompleteIfReady(Team $team): bool
    {
        $scores = $this->forTeam($team);

        if (! $scores['is_complete']) {
            return false;
        }

        if ($team->status !== TeamStatus::ScoringCompleted) {
            $team->update([
                'status' => TeamStatus::ScoringCompleted,
                'scoring_completed_at' => now(),
            ]);
        }

        return true;
    }
}
