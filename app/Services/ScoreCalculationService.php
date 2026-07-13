<?php

namespace App\Services;

use App\Enums\TeamStatus;
use App\Models\Judge;
use App\Models\TalentShow;
use App\Models\Team;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ScoreCalculationService
{
    public function forTeam(Team $team, ?TalentShow $talentShow = null): array
    {
        $talentShow ??= $team->talentShow;
        $activeJudgeIds = $talentShow->activeJudges()->pluck('id');
        $activeJudgesCount = $activeJudgeIds->count();
        $votes = $team->votes()->whereIn('judge_id', $activeJudgeIds)->get();
        $votesCount = $votes->count();

        $totalScore = $votes->sum('score');
        $averageScore = $votesCount > 0 ? round($totalScore / $votesCount, 2) : 0;
        $maximumScore = $activeJudgesCount * 10;
        $numberOfTens = $votes->where('score', 10)->count();
        $numberOfNines = $votes->where('score', 9)->count();

        return [
            'votes_count' => $votesCount,
            'active_judges_count' => $activeJudgesCount,
            'total_score' => $totalScore,
            'average_score' => $averageScore,
            'maximum_score' => $maximumScore,
            'is_complete' => $votesCount >= $activeJudgesCount && $activeJudgesCount > 0,
            'number_of_tens' => $numberOfTens,
            'number_of_nines' => $numberOfNines,
        ];
    }

    public function votesProgress(TalentShow $talentShow, ?Team $team = null): array
    {
        $team ??= $talentShow->currentTeam;

        if (! $team) {
            return ['voted' => 0, 'total' => 0];
        }

        $activeJudgeIds = $talentShow->activeJudges()->pluck('id');
        $total = $activeJudgeIds->count();
        $voted = $team->votes()->whereIn('judge_id', $activeJudgeIds)->count();

        return ['voted' => $voted, 'total' => $total];
    }

    public function judgeVoteStatus(TalentShow $talentShow, Team $team): array
    {
        $judges = $talentShow->activeJudges()->get();
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
