<?php

namespace App\Services;

use App\Enums\SubmitResult;
use App\Models\BrasileiraoContest;
use App\Models\BrasileiraoInstitution;
use App\Models\BrasileiraoMember;
use App\Models\BrasileiraoWeeklyChallenge;
use App\Models\Submission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BrasileiraoLeaderboardService
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function metricColumn(string $rankingType): string
    {
        return match ($rankingType) {
            'cpu_time' => 'execution_time',
            'memory_peak' => 'execution_memory',
            'submission_time' => 'created_at',
            'code_lines' => 'code_lines',
            'tokens' => 'num_tokens',
            'test_cases' => 'num_test_cases',
        };
    }

    /** 'bixos'|'veteranos'|'all' → array of role strings */
    public function eligibleRoles(string $league): array
    {
        return match ($league) {
            'bixos' => ['bixo'],
            'veteranos' => ['veteran'],
            default => ['bixo', 'veteran'],
        };
    }

    public function pointsColumn(string $league): string
    {
        return $league === 'bixos' ? 'bixos_points' : 'veterans_points';
    }

    public function solutionsColumn(string $league): string
    {
        return $league === 'bixos' ? 'bixos_solutions' : 'veterans_solutions';
    }

    /** Returns whether a metric value qualifies for the 5% technical bonus tie */
    public function withinTie(bool $isTestCases, float|int $value, float|int $firstValue): bool
    {
        if ($firstValue == 0) {
            return true;
        }

        return $isTestCases
            ? ($firstValue - $value) / $firstValue <= 0.05
            : abs($value - $firstValue) / $firstValue <= 0.05;
    }

    // -------------------------------------------------------------------------
    // Challenge leaderboard
    // -------------------------------------------------------------------------

    /**
     * Returns the ordered leaderboard for a weekly challenge, filtered to a
     * specific league ('bixos', 'veteranos', or 'all' for both combined).
     *
     * Each entry is an object with:
     *   - position    (int, 1-based)
     *   - submission  (Submission, with user loaded)
     *   - institution (BrasileiraoInstitution|null)
     *   - hasBonus    (bool)
     *   - metricValue (int|float|null — normalised: submission_time as seconds from start)
     */
    public function challengeLeaderboard(BrasileiraoWeeklyChallenge $challenge, string $league): Collection
    {
        $roles = $this->eligibleRoles($league);
        $metricColumn = $this->metricColumn($challenge->ranking_type);
        $isTestCases = $challenge->ranking_type === 'test_cases';
        $aggregate = $isTestCases ? 'MAX' : 'MIN';

        $eligibleUserIds = BrasileiraoMember::whereIn('role', $roles)->pluck('user_id');

        $bestPerUser = Submission::where('problem_id', $challenge->problem_id)
            ->whereBetween('created_at', [$challenge->start_at, $challenge->end_at])
            ->whereIn('user_id', $eligibleUserIds)
            ->when($isTestCases,
                fn ($q) => $q->where('num_test_cases', '>=', 5),
                fn ($q) => $q->where('result', SubmitResult::Accepted)
            )
            ->selectRaw("user_id, {$aggregate}({$metricColumn}) as best_value")
            ->groupBy('user_id');

        $submissions = Submission::where('submissions.problem_id', $challenge->problem_id)
            ->whereBetween('submissions.created_at', [$challenge->start_at, $challenge->end_at])
            ->whereIn('submissions.user_id', $eligibleUserIds)
            ->when($isTestCases,
                fn ($q) => $q->where('submissions.num_test_cases', '>=', 5),
                fn ($q) => $q->where('submissions.result', SubmitResult::Accepted)
            )
            ->joinSub($bestPerUser, 'best', function ($join) use ($metricColumn) {
                $join->on('submissions.user_id', '=', 'best.user_id')
                    ->whereColumn("submissions.{$metricColumn}", 'best.best_value');
            })
            ->when($isTestCases,
                fn ($q) => $q->orderByDesc('best.best_value'),
                fn ($q) => $q->orderBy('best.best_value')
            )
            ->orderBy('submissions.id')
            ->select('submissions.*')
            ->with('user')
            ->get()
            ->unique('user_id')
            ->values();

        if ($submissions->isEmpty()) {
            return collect();
        }

        $memberMap = BrasileiraoMember::whereIn('user_id', $submissions->pluck('user_id'))
            ->with('institution')
            ->get()
            ->keyBy('user_id');

        $firstSubmission = $submissions->first();
        $firstValue = $challenge->ranking_type === 'submission_time'
            ? $firstSubmission->created_at->diffInSeconds($challenge->start_at)
            : $firstSubmission->{$metricColumn};

        return $submissions->map(function ($submission, $index) use (
            $challenge, $metricColumn, $isTestCases, $firstValue, $memberMap
        ) {
            $value = $challenge->ranking_type === 'submission_time'
                ? $submission->created_at->diffInSeconds($challenge->start_at)
                : $submission->{$metricColumn};

            $member = $memberMap->get($submission->user_id);

            return (object) [
                'position' => $index + 1,
                'submission' => $submission,
                'institution' => $member?->institution,
                'hasBonus' => $this->withinTie($isTestCases, $value, $firstValue),
                'metricValue' => $value,
            ];
        });
    }

    /**
     * Applies challenge points and solutions to BrasileiraoInstitution for one league.
     * $league must be 'bixos' or 'veteranos' (not 'all').
     */
    public function applyChallengeScore(BrasileiraoWeeklyChallenge $challenge, string $league): void
    {
        $leaderboard = $this->challengeLeaderboard($challenge, $league);
        $pointsCol = $this->pointsColumn($league);
        $solutionsCol = $this->solutionsColumn($league);

        $byInstitution = $leaderboard
            ->filter(fn ($entry) => $entry->institution !== null)
            ->groupBy(fn ($entry) => $entry->institution->id);

        foreach ($byInstitution as $institutionId => $entries) {
            $points = 1;
            $solutions = $entries->count();

            if ($entries->contains(fn ($e) => $e->hasBonus)) {
                $points += 1;
            }

            dump("[applyChallengeScore] problem=\"{$challenge->problem->title}\" league={$league} institution={$entries->first()->institution->acronym} points={$points} solutions={$solutions}");

            BrasileiraoInstitution::where('id', $institutionId)->increment($pointsCol, $points);
            BrasileiraoInstitution::where('id', $institutionId)->increment($solutionsCol, $solutions);
        }
    }

    // -------------------------------------------------------------------------
    // Contest scoring
    // -------------------------------------------------------------------------

    /**
     * Returns per-institution score data for a BrasileiraoContest in a given league.
     * $league must be 'bixos' or 'veteranos'.
     *
     * Returns: Collection<institutionId, object{institution, problems, bonus, points}>
     */
    public function contestScoreData(BrasileiraoContest $bc, string $league): Collection
    {
        $contest = $bc->contest;
        $roles = $this->eligibleRoles($league);
        $memberMap = BrasileiraoMember::whereIn('role', $roles)
            ->with('institution')
            ->get()
            ->keyBy('user_id');

        if ($memberMap->isEmpty()) {
            return collect();
        }

        $competitors = $contest->competitors()->get();

        // competitor_id → Collection of institution_ids
        $competitorInstitutions = collect();
        foreach ($competitors as $competitor) {
            $instIds = collect();

            if ($contest->individual && $competitor->user_id) {
                $member = $memberMap->get($competitor->user_id);
                if ($member) {
                    $instIds->push($member->institution_id);
                }
            } elseif ($competitor->team_id) {
                $teamUserIds = DB::table('team_user')
                    ->where('team_id', $competitor->team_id)
                    ->where('accepted', true)
                    ->pluck('user_id');

                foreach ($teamUserIds as $uid) {
                    $member = $memberMap->get($uid);
                    if ($member) {
                        $instIds->push($member->institution_id);
                    }
                }
                $instIds = $instIds->unique();
            }

            if ($instIds->isNotEmpty()) {
                $competitorInstitutions->put($competitor->id, $instIds);
            }
        }

        if ($competitorInstitutions->isEmpty()) {
            return collect();
        }

        // Problems solved per institution
        $solvedRows = DB::table('competitor_problem')
            ->whereIn('competitor_id', $competitorInstitutions->keys())
            ->where('score', '>', 0)
            ->select('competitor_id', 'problem_id')
            ->get();

        $problemsByInstitution = collect();
        foreach ($solvedRows as $row) {
            foreach ($competitorInstitutions->get($row->competitor_id, collect()) as $instId) {
                if (! $problemsByInstitution->has($instId)) {
                    $problemsByInstitution->put($instId, collect());
                }
                $problemsByInstitution->get($instId)->push($row->problem_id);
            }
        }

        // Top-3 institutions for bonus (ranked by score DESC, penalty ASC)
        $ranked = $competitors->sortByDesc('score')->sortBy('penality')->values();
        $bonusInstitutionIds = collect();
        foreach ($ranked as $competitor) {
            if ($bonusInstitutionIds->count() >= 3) {
                break;
            }
            foreach ($competitorInstitutions->get($competitor->id, collect()) as $instId) {
                if (! $bonusInstitutionIds->contains($instId)) {
                    $bonusInstitutionIds->push($instId);
                }
                if ($bonusInstitutionIds->count() >= 3) {
                    break;
                }
            }
        }

        $institutionMap = BrasileiraoInstitution::whereIn('id',
            $problemsByInstitution->keys()->merge($bonusInstitutionIds)->unique()
        )->get()->keyBy('id');

        $allIds = $problemsByInstitution->keys()->merge($bonusInstitutionIds)->unique();

        return $allIds->mapWithKeys(function ($instId) use (
            $problemsByInstitution, $bonusInstitutionIds, $institutionMap
        ) {
            $uniqueProblems = ($problemsByInstitution->get($instId) ?? collect())->unique()->count();
            $bonus = $bonusInstitutionIds->contains($instId) ? 1 : 0;

            return [$instId => (object) [
                'institution' => $institutionMap->get($instId),
                'problems' => $uniqueProblems,
                'bonus' => $bonus,
                'points' => $uniqueProblems + $bonus,
            ]];
        });
    }

    /**
     * Applies contest points and solutions to BrasileiraoInstitution for one league.
     * $league must be 'bixos' or 'veteranos'.
     */
    public function applyContestScore(BrasileiraoContest $bc, string $league): void
    {
        $data = $this->contestScoreData($bc, $league);
        $pointsCol = $this->pointsColumn($league);
        $solutionsCol = $this->solutionsColumn($league);

        foreach ($data as $instId => $entry) {
            dump("[applyContestScore] contest=\"{$bc->contest->title}\" league={$league} institution={$entry->institution?->acronym} uniqueProblems={$entry->problems} bonus={$entry->bonus} points={$entry->points}");

            BrasileiraoInstitution::where('id', $instId)->increment($pointsCol, $entry->points);
            BrasileiraoInstitution::where('id', $instId)->increment($solutionsCol, $entry->problems);
        }
    }

    // -------------------------------------------------------------------------
    // Institution rankings
    // -------------------------------------------------------------------------

    /**
     * Returns BrasileiraoInstitution collection ordered by league points.
     * $league: 'bixos' or 'veteranos'
     */
    public function institutionLeaderboard(string $league): Collection
    {
        $countRelation = $league === 'bixos' ? 'bixos_count' : 'veterans_count';
        $pointsCol = $this->pointsColumn($league);

        return BrasileiraoInstitution::withCount(['members', 'bixos', 'veterans', 'coaches'])
            ->having($countRelation, '>', 0)
            ->orderByDesc($pointsCol)
            ->orderByDesc($countRelation)
            ->get();
    }

    // -------------------------------------------------------------------------
    // Per-member stats for an institution
    // -------------------------------------------------------------------------

    /**
     * Returns per-member stats for a given league within an institution.
     * $league: 'bixos' or 'veteranos'
     *
     * Each entry: object{member, solved, bonus, attempts, score}
     */
    public function institutionMemberStats(BrasileiraoInstitution $institution, string $league): Collection
    {
        $roles = $this->eligibleRoles($league);
        $challengeLeagues = $league === 'bixos' ? ['bixos', 'all'] : ['veteranos', 'all'];

        $members = $institution->members()->with('user')->get();
        $eligible = $members->whereIn('role', $roles);
        $eligibleUserIds = $eligible->pluck('user_id');

        if ($eligibleUserIds->isEmpty()) {
            return collect();
        }

        $stats = $eligibleUserIds->mapWithKeys(fn ($uid) => [
            $uid => ['solved' => 0, 'bonus' => 0, 'attempts' => 0],
        ])->toArray();

        $challenges = BrasileiraoWeeklyChallenge::whereIn('league', $challengeLeagues)
            ->where('start_at', '<=', now())
            ->get();

        $allRoleUserIds = BrasileiraoMember::whereIn('role', $roles)->pluck('user_id');
        $acceptedDescription = SubmitResult::getDescription(SubmitResult::Accepted);

        foreach ($challenges as $challenge) {
            $isTestCases = $challenge->ranking_type === 'test_cases';
            $metricColumn = $this->metricColumn($challenge->ranking_type);
            $aggregate = $isTestCases ? 'MAX' : 'MIN';

            $windowSubmissions = Submission::whereBetween('created_at', [$challenge->start_at, $challenge->end_at])
                ->where('problem_id', $challenge->problem_id)
                ->whereIn('user_id', $eligibleUserIds)
                ->get();

            foreach ($windowSubmissions->groupBy('user_id') as $uid => $subs) {
                if (isset($stats[$uid])) {
                    $stats[$uid]['attempts'] += $subs->count();
                }
            }

            $qualified = $isTestCases
                ? $windowSubmissions->where('num_test_cases', '>=', 5)
                : $windowSubmissions->where('result', $acceptedDescription);

            $bestPerUser = $qualified->groupBy('user_id')->map(function ($subs) use ($metricColumn, $challenge, $isTestCases) {
                return $subs->sortBy(function ($s) use ($metricColumn, $challenge, $isTestCases) {
                    $v = $challenge->ranking_type === 'submission_time'
                        ? $s->created_at->diffInSeconds($challenge->start_at)
                        : $s->{$metricColumn};

                    return $isTestCases ? -$v : $v;
                })->first();
            });

            foreach ($bestPerUser as $uid => $_) {
                if (isset($stats[$uid])) {
                    $stats[$uid]['solved']++;
                }
            }

            if ($bestPerUser->isNotEmpty()) {
                $globalBest = Submission::where('problem_id', $challenge->problem_id)
                    ->whereBetween('created_at', [$challenge->start_at, $challenge->end_at])
                    ->whereIn('user_id', $allRoleUserIds)
                    ->when($isTestCases,
                        fn ($q) => $q->where('num_test_cases', '>=', 5),
                        fn ($q) => $q->where('result', SubmitResult::Accepted)
                    )
                    ->selectRaw("user_id, {$aggregate}({$metricColumn}) as best_value")
                    ->groupBy('user_id')
                    ->when($isTestCases,
                        fn ($q) => $q->orderByDesc('best_value'),
                        fn ($q) => $q->orderBy('best_value')
                    )
                    ->first();

                if ($globalBest) {
                    $firstValue = $challenge->ranking_type === 'submission_time'
                        ? \Carbon\Carbon::parse($globalBest->best_value)->diffInSeconds($challenge->start_at)
                        : $globalBest->best_value;

                    foreach ($bestPerUser as $uid => $submission) {
                        $value = $challenge->ranking_type === 'submission_time'
                            ? $submission->created_at->diffInSeconds($challenge->start_at)
                            : $submission->{$metricColumn};

                        if ($this->withinTie($isTestCases, $value, $firstValue) && isset($stats[$uid])) {
                            $stats[$uid]['bonus']++;
                        }
                    }
                }
            }
        }

        return $eligible->map(function ($member) use ($stats) {
            $s = $stats[$member->user_id] ?? ['solved' => 0, 'bonus' => 0, 'attempts' => 0];

            return (object) [
                'member' => $member,
                'solved' => $s['solved'],
                'bonus' => $s['bonus'],
                'attempts' => $s['attempts'],
                'score' => $s['solved'] + $s['bonus'],
            ];
        })->sort(function ($a, $b) {
            if ($b->score !== $a->score) {
                return $b->score - $a->score;
            }

            return $a->attempts - $b->attempts;
        })->values();
    }
}
