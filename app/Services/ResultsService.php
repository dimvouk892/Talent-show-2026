<?php

namespace App\Services;

use App\Models\TalentShow;
use App\Models\Team;

class ResultsService
{
    public function __construct(
        protected ScoreCalculationService $scoreCalculationService,
    ) {}

    public function getRanking(TalentShow $talentShow): array
    {
        $teams = $talentShow->activeTeams()->ordered()->get();

        $results = $teams->map(function (Team $team) use ($talentShow) {
            $scores = $this->scoreCalculationService->forTeam($team, $talentShow);

            return [
                'team' => $team,
                'total_score' => $scores['total_score'],
                'average_score' => $scores['average_score'],
                'maximum_score' => $scores['maximum_score'],
                'votes_count' => $scores['votes_count'],
                'active_judges_count' => $scores['active_judges_count'],
                'number_of_twelves' => $scores['number_of_twelves'],
                'number_of_tens' => $scores['number_of_tens'],
                'number_of_nines' => $scores['number_of_nines'],
                'is_complete' => $scores['is_complete'],
            ];
        })->filter(fn ($result) => $result['votes_count'] > 0);

        $complete = $results->filter(fn ($result) => $result['is_complete']);

        $sortedComplete = $complete->sort(function ($a, $b) {
            if ($a['total_score'] !== $b['total_score']) {
                return $b['total_score'] <=> $a['total_score'];
            }

            if ($a['number_of_twelves'] !== $b['number_of_twelves']) {
                return $b['number_of_twelves'] <=> $a['number_of_twelves'];
            }

            if ($a['number_of_tens'] !== $b['number_of_tens']) {
                return $b['number_of_tens'] <=> $a['number_of_tens'];
            }

            return $b['number_of_nines'] <=> $a['number_of_nines'];
        })->values();

        $sortedIncomplete = $results
            ->filter(fn ($result) => ! $result['is_complete'])
            ->sortByDesc('total_score')
            ->values();

        $ranking = [];
        $position = 1;

        foreach ($sortedComplete as $index => $item) {
            if ($index > 0) {
                $prev = $sortedComplete[$index - 1];
                $isTied = $item['total_score'] === $prev['total_score']
                    && $item['number_of_twelves'] === $prev['number_of_twelves']
                    && $item['number_of_tens'] === $prev['number_of_tens']
                    && $item['number_of_nines'] === $prev['number_of_nines'];

                if (! $isTied) {
                    $position = $index + 1;
                }
            }

            $item['ranking_position'] = $position;
            $ranking[] = $item;
        }

        foreach ($sortedIncomplete as $item) {
            $item['ranking_position'] = null;
            $ranking[] = $item;
        }

        return $ranking;
    }

    public function getWinnerCandidates(TalentShow $talentShow): array
    {
        $ranking = array_values(array_filter(
            $this->getRanking($talentShow),
            fn ($item) => $item['is_complete']
        ));

        if (empty($ranking)) {
            return [];
        }

        $top = $ranking[0];

        return array_values(array_filter($ranking, function ($item) use ($top) {
            return $item['total_score'] === $top['total_score']
                && $item['number_of_twelves'] === $top['number_of_twelves']
                && $item['number_of_tens'] === $top['number_of_tens']
                && $item['number_of_nines'] === $top['number_of_nines'];
        }));
    }

    public function getTiedTeams(TalentShow $talentShow): array
    {
        $candidates = $this->getWinnerCandidates($talentShow);

        return count($candidates) > 1 ? $candidates : [];
    }

    public function getTopPlaces(TalentShow $talentShow, int $limit = 5): array
    {
        $complete = array_values(array_filter(
            $this->getRanking($talentShow),
            fn (array $item) => $item['is_complete'] && $item['ranking_position'] !== null
        ));

        return array_slice($complete, 0, max(0, $limit));
    }

    public function getPodiumRevealState(TalentShow $talentShow, int $limit = 5): array
    {
        $topPlaces = $this->getTopPlaces($talentShow, $limit);
        $totalSteps = count($topPlaces);
        $step = max(0, min((int) $talentShow->podium_reveal_step, $totalSteps));
        $revealOrder = array_reverse($topPlaces);
        $revealed = array_slice($revealOrder, 0, $step);
        $current = $step > 0 ? ($revealOrder[$step - 1] ?? null) : null;
        $next = $step < $totalSteps ? ($revealOrder[$step] ?? null) : null;

        return [
            'top_places' => $topPlaces,
            'reveal_order' => $revealOrder,
            'revealed' => $revealed,
            'current' => $current,
            'next' => $next,
            'total_steps' => $totalSteps,
            'step' => $step,
            'is_complete' => $totalSteps > 0 && $step >= $totalSteps,
        ];
    }

    public function getWinner(TalentShow $talentShow): ?array
    {
        if (! $talentShow->winner_revealed || ! $talentShow->winner_team_id) {
            return null;
        }

        $ranking = $this->getRanking($talentShow);

        foreach ($ranking as $item) {
            if ($item['team']->id === $talentShow->winner_team_id) {
                return $item;
            }
        }

        $team = $talentShow->winnerTeam;
        $scores = $this->scoreCalculationService->forTeam($team, $talentShow);

        return [
            'team' => $team,
            'total_score' => $scores['total_score'],
            'average_score' => $scores['average_score'],
            'maximum_score' => $scores['maximum_score'],
            'ranking_position' => 1,
        ];
    }

    public function getProgress(TalentShow $talentShow): array
    {
        $teams = $talentShow->activeTeams()->count();
        $completed = $talentShow->teams()
            ->whereIn('status', ['scoring_completed', 'presented'])
            ->count();

        return [
            'total_teams' => $teams,
            'completed_teams' => $completed,
            'percentage' => $teams > 0 ? round(($completed / $teams) * 100) : 0,
        ];
    }

    public function getDetailedReport(TalentShow $talentShow): array
    {
        $scoringJudges = $talentShow->scoringJudges()->get();
        $finalVoter = $talentShow->finalVoter();
        $judges = $scoringJudges;
        if ($finalVoter) {
            $judges = $scoringJudges->concat([$finalVoter])->values();
        }

        $ranking = $this->getRanking($talentShow);
        $rankingByTeamId = collect($ranking)->keyBy(fn (array $item) => $item['team']->id);

        $teams = $talentShow->activeTeams()->ordered()->get();

        $rows = $teams->map(function (Team $team) use ($talentShow, $rankingByTeamId, $scoringJudges, $finalVoter) {
            $ranked = $rankingByTeamId->get($team->id);
            $scores = $ranked ?? $this->scoreCalculationService->forTeam($team, $talentShow);

            $judgeStatus = $this->scoreCalculationService->judgeVoteStatus($talentShow, $team);
            $judgeScores = array_map(function (array $status) {
                return [
                    'judge_id' => $status['judge']->id,
                    'judge_number' => $status['judge_number'],
                    'judge_name' => $status['judge']->name,
                    'score' => $status['score'],
                    'has_voted' => $status['has_voted'],
                    'is_admin_edited' => (bool) ($status['vote']?->is_admin_edited),
                    'is_final_voter' => false,
                    'vote_id' => $status['vote']?->id,
                ];
            }, $judgeStatus);

            if ($finalVoter) {
                $finalVote = $team->votes()->where('judge_id', $finalVoter->id)->first();
                $judgeScores[] = [
                    'judge_id' => $finalVoter->id,
                    'judge_number' => $finalVoter->display_order,
                    'judge_name' => $finalVoter->name,
                    'score' => $finalVote?->score,
                    'has_voted' => $finalVote !== null,
                    'is_admin_edited' => (bool) ($finalVote?->is_admin_edited),
                    'is_final_voter' => true,
                    'vote_id' => $finalVote?->id,
                ];
            }

            return [
                'team' => $team,
                'total_score' => $scores['total_score'] ?? 0,
                'average_score' => $scores['average_score'] ?? 0,
                'maximum_score' => $scores['maximum_score'] ?? 0,
                'votes_count' => $scores['votes_count'] ?? 0,
                'active_judges_count' => $scores['active_judges_count'] ?? $scoringJudges->count(),
                'number_of_twelves' => $scores['number_of_twelves'] ?? 0,
                'number_of_tens' => $scores['number_of_tens'] ?? 0,
                'number_of_nines' => $scores['number_of_nines'] ?? 0,
                'is_complete' => $scores['is_complete'] ?? false,
                'ranking_position' => $ranked['ranking_position'] ?? null,
                'judge_scores' => $judgeScores,
            ];
        })->all();

        // Keep ranking order for teams with votes, then remaining teams by display order
        usort($rows, function (array $a, array $b) {
            $aPos = $a['ranking_position'];
            $bPos = $b['ranking_position'];

            if ($aPos !== null && $bPos !== null && $aPos !== $bPos) {
                return $aPos <=> $bPos;
            }

            if ($aPos !== null && $bPos === null) {
                return -1;
            }

            if ($aPos === null && $bPos !== null) {
                return 1;
            }

            if (($a['votes_count'] > 0) !== ($b['votes_count'] > 0)) {
                return $b['votes_count'] <=> $a['votes_count'];
            }

            return $a['team']->display_order <=> $b['team']->display_order;
        });

        $completeTeams = count(array_filter($rows, fn (array $item) => $item['is_complete']));
        $teamsWithVotes = count(array_filter($rows, fn (array $item) => $item['votes_count'] > 0 || collect($item['judge_scores'])->contains('has_voted', true)));

        return [
            'summary' => [
                'total_teams' => $teams->count(),
                'teams_with_votes' => $teamsWithVotes,
                'complete_teams' => $completeTeams,
                'incomplete_teams' => $teams->count() - $completeTeams,
                'active_judges' => $judges->count(),
                'total_votes' => $talentShow->votes()->count(),
                'show_status' => $talentShow->status->label(),
                'event_date' => $talentShow->event_date,
                'venue' => $talentShow->venue,
                'generated_at' => now(),
            ],
            'judges' => $judges,
            'ranking' => $rows,
        ];
    }
}
