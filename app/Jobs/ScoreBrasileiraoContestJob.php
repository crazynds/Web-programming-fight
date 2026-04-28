<?php

namespace App\Jobs;

use App\Models\BrasileiraoContest;
use App\Models\BrasileiraoInstitution;
use App\Models\BrasileiraoMember;
use App\Models\Competitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ScoreBrasileiraoContestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public BrasileiraoContest $brasileiraoContest) {}

    public function handle(): void
    {
        $bc = $this->brasileiraoContest;

        if ($bc->scored_at !== null) {
            return;
        }

        DB::transaction(function () use ($bc) {
            $contest = $bc->contest;

            $leaguesToScore = match ($bc->league) {
                'bixos'     => ['bixos'],
                'veteranos' => ['veteranos'],
                default     => ['bixos', 'veteranos'],
            };

            foreach ($leaguesToScore as $targetLeague) {
                $this->scoreLeague($bc, $targetLeague);
            }

            $bc->update(['scored_at' => now()]);
        });
    }

    private function scoreLeague(BrasileiraoContest $bc, string $targetLeague): void
    {
        $contest      = $bc->contest;
        $roles        = $targetLeague === 'bixos' ? ['bixo'] : ['veteran'];
        $pointsCol    = $targetLeague === 'bixos' ? 'bixos_points'    : 'veterans_points';
        $solutionsCol = $targetLeague === 'bixos' ? 'bixos_solutions' : 'veterans_solutions';

        // Map user_id → institution_id for eligible brasileirao members
        $memberMap = BrasileiraoMember::whereIn('role', $roles)
            ->pluck('institution_id', 'user_id');

        if ($memberMap->isEmpty()) {
            return;
        }

        // Resolve competitor → set of institution_ids
        // For individual contests: competitor.user_id → institution
        // For team contests: team members → institutions
        $competitors = $contest->competitors()->with('scores')->get();

        $competitorInstitutions = collect(); // competitor_id → collection of institution_ids

        foreach ($competitors as $competitor) {
            $instIds = collect();

            if ($contest->individual && $competitor->user_id) {
                $instId = $memberMap->get($competitor->user_id);
                if ($instId) {
                    $instIds->push($instId);
                }
            } elseif ($competitor->team_id) {
                $teamUserIds = DB::table('team_user')
                    ->where('team_id', $competitor->team_id)
                    ->where('accepted', true)
                    ->pluck('user_id');

                foreach ($teamUserIds as $uid) {
                    $instId = $memberMap->get($uid);
                    if ($instId) {
                        $instIds->push($instId);
                    }
                }
                $instIds = $instIds->unique();
            }

            if ($instIds->isNotEmpty()) {
                $competitorInstitutions->put($competitor->id, $instIds);
            }
        }

        if ($competitorInstitutions->isEmpty()) {
            return;
        }

        // --- Problems solved per institution ---
        // competitor_problem: score > 0 means solved
        $solvedRows = DB::table('competitor_problem')
            ->whereIn('competitor_id', $competitorInstitutions->keys())
            ->where('score', '>', 0)
            ->select('competitor_id', 'problem_id')
            ->get();

        // institution_id → set of problem_ids solved
        $problemsByInstitution = collect();

        foreach ($solvedRows as $row) {
            $instIds = $competitorInstitutions->get($row->competitor_id, collect());
            foreach ($instIds as $instId) {
                if (! $problemsByInstitution->has($instId)) {
                    $problemsByInstitution->put($instId, collect());
                }
                $problemsByInstitution->get($instId)->push($row->problem_id);
            }
        }

        // --- Institution ranking (for bonus) ---
        // Rank competitors by score desc, penality asc; first 3 unique institutions get bonus
        $rankedCompetitors = $competitors->sortByDesc('score')->sortBy('penality')->values();

        $bonusInstitutionIds = collect();
        foreach ($rankedCompetitors as $competitor) {
            if ($bonusInstitutionIds->count() >= 3) {
                break;
            }
            $instIds = $competitorInstitutions->get($competitor->id, collect());
            foreach ($instIds as $instId) {
                if (! $bonusInstitutionIds->contains($instId)) {
                    $bonusInstitutionIds->push($instId);
                }
                if ($bonusInstitutionIds->count() >= 3) {
                    break;
                }
            }
        }

        // --- Apply points ---
        foreach ($problemsByInstitution as $instId => $problemIds) {
            $uniqueProblems = $problemIds->unique()->count();
            $bonus = $bonusInstitutionIds->contains($instId) ? 1 : 0;
            $points = $uniqueProblems + $bonus;

            BrasileiraoInstitution::where('id', $instId)->increment($pointsCol, $points);
            BrasileiraoInstitution::where('id', $instId)->increment($solutionsCol, $uniqueProblems);
        }

        // Bonus for institutions in top 3 that didn't solve any problem (edge case)
        foreach ($bonusInstitutionIds as $instId) {
            if (! $problemsByInstitution->has($instId)) {
                BrasileiraoInstitution::where('id', $instId)->increment($pointsCol, 1);
            }
        }
    }
}
