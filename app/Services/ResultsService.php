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
                && $item['number_of_tens'] === $top['number_of_tens']
                && $item['number_of_nines'] === $top['number_of_nines'];
        }));
    }

    public function getTiedTeams(TalentShow $talentShow): array
    {
        $candidates = $this->getWinnerCandidates($talentShow);

        return count($candidates) > 1 ? $candidates : [];
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
        $judges = $talentShow->activeJudges()->get();
        $ranking = $this->getRanking($talentShow);

        $rankingWithDetails = array_map(function (array $item) use ($talentShow) {
            $judgeStatus = $this->scoreCalculationService->judgeVoteStatus($talentShow, $item['team']);

            $item['judge_scores'] = array_map(function (array $status) {
                return [
                    'judge_id' => $status['judge']->id,
                    'judge_number' => $status['judge_number'],
                    'judge_name' => $status['judge']->name,
                    'score' => $status['score'],
                    'has_voted' => $status['has_voted'],
                    'is_admin_edited' => (bool) ($status['vote']?->is_admin_edited),
                ];
            }, $judgeStatus);

            return $item;
        }, $ranking);

        $completeTeams = count(array_filter($rankingWithDetails, fn (array $item) => $item['is_complete']));

        return [
            'summary' => [
                'total_teams' => $talentShow->activeTeams()->count(),
                'teams_with_votes' => count($rankingWithDetails),
                'complete_teams' => $completeTeams,
                'incomplete_teams' => count($rankingWithDetails) - $completeTeams,
                'active_judges' => $judges->count(),
                'total_votes' => $talentShow->votes()->count(),
                'show_status' => $talentShow->status->label(),
                'event_date' => $talentShow->event_date,
                'venue' => $talentShow->venue,
                'generated_at' => now(),
            ],
            'judges' => $judges,
            'ranking' => $rankingWithDetails,
        ];
    }
}
