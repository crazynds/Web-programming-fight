<?php

namespace App\Jobs;

use App\Enums\SubmitResult;
use App\Models\BrasileiraoInstitution;
use App\Models\BrasileiraoMember;
use App\Models\BrasileiraoWeeklyChallenge;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ScoreBrasileiraoChallengeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public BrasileiraoWeeklyChallenge $challenge) {}

    public function handle(): void
    {
        $challenge = $this->challenge->fresh();

        // Already scored or challenge not ended yet (end_at was extended after dispatch)
        if ($challenge->scored_at !== null || now()->lt($challenge->end_at)) {
            return;
        }

        DB::transaction(function () use ($challenge) {
            // Re-check inside transaction to prevent race conditions
            $challenge = BrasileiraoWeeklyChallenge::lockForUpdate()->find($challenge->id);
            if ($challenge->scored_at !== null) {
                return;
            }

            // Score each league separately so bixos_points and veterans_points stay independent
            $leaguesToScore = match ($challenge->league) {
                'bixos'     => ['bixos'],
                'veteranos' => ['veteranos'],
                default     => ['bixos', 'veteranos'],
            };

            foreach ($leaguesToScore as $targetLeague) {
                $this->scoreLeague($challenge, $targetLeague);
            }

            $challenge->update(['scored_at' => now()]);
        });
    }

    private function scoreLeague(BrasileiraoWeeklyChallenge $challenge, string $targetLeague): void
    {
        $roles = $targetLeague === 'bixos' ? ['bixo'] : ['veteran'];
        $pointsCol   = $targetLeague === 'bixos' ? 'bixos_points'   : 'veterans_points';
        $solutionsCol = $targetLeague === 'bixos' ? 'bixos_solutions' : 'veterans_solutions';

        $eligibleUserIds = BrasileiraoMember::whereIn('role', $roles)->pluck('user_id');

        $isTestCases = $challenge->ranking_type === 'test_cases';

        $metricColumn = match ($challenge->ranking_type) {
            'cpu_time'        => 'execution_time',
            'memory_peak'     => 'execution_memory',
            'submission_time' => 'created_at',
            'code_lines'      => 'code_lines',
            'test_cases'      => 'num_test_cases',
        };

        $aggregate = $isTestCases ? 'MAX' : 'MIN';

        $bestPerUser = Submission::where('problem_id', $challenge->problem_id)
            ->whereBetween('created_at', [$challenge->start_at, $challenge->end_at])
            ->whereIn('user_id', $eligibleUserIds)
            ->when($isTestCases,
                fn ($q) => $q->where('num_test_cases', '>=', 5),
                fn ($q) => $q->where('result', SubmitResult::Accepted)
            )
            ->selectRaw("user_id, {$aggregate}({$metricColumn}) as best_value")
            ->groupBy('user_id');

        $leaderboard = Submission::where('submissions.problem_id', $challenge->problem_id)
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
            ->get()
            ->unique('user_id')
            ->values();

        if ($leaderboard->isEmpty()) {
            return;
        }

        // Map user_id → institution_id
        $memberMap = BrasileiraoMember::whereIn('user_id', $leaderboard->pluck('user_id'))
            ->pluck('institution_id', 'user_id');

        // Determine institutions that qualify for the 1st-place bonus (within 5% technical tie)
        $firstSubmission = $leaderboard->first();
        $firstValue = $challenge->ranking_type === 'submission_time'
            ? $firstSubmission->created_at->diffInSeconds($challenge->start_at)
            : $firstSubmission->{$metricColumn};

        $bonusInstitutionIds = collect();
        foreach ($leaderboard as $submission) {
            $value = $challenge->ranking_type === 'submission_time'
                ? $submission->created_at->diffInSeconds($challenge->start_at)
                : $submission->{$metricColumn};

            // Higher-is-better (test_cases): within 5% below the max
            // Lower-is-better (others): within 5% above the min
            $withinTie = $isTestCases
                ? ($firstValue == 0 || ($firstValue - $value) / $firstValue <= 0.05)
                : ($firstValue == 0 || abs($value - $firstValue) / $firstValue <= 0.05);

            if ($withinTie) {
                $instId = $memberMap->get($submission->user_id);
                if ($instId) {
                    $bonusInstitutionIds->push($instId);
                }
            }
        }
        $bonusInstitutionIds = $bonusInstitutionIds->unique();

        // Group solvers by institution
        $solversByInstitution = $leaderboard->groupBy(fn ($s) => $memberMap->get($s->user_id));

        foreach ($solversByInstitution as $institutionId => $solvers) {
            if (! $institutionId) {
                continue; // solver not registered in brasileirao
            }

            $points = 1; // at least one member solved
            $solutions = $solvers->count();

            if ($bonusInstitutionIds->contains($institutionId)) {
                $points += 1; // bonus for 1st place or technical tie (within 5%)
            }

            BrasileiraoInstitution::where('id', $institutionId)->increment($pointsCol, $points);
            BrasileiraoInstitution::where('id', $institutionId)->increment($solutionsCol, $solutions);
        }
    }
}
