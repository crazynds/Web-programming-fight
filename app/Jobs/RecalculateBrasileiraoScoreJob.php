<?php

namespace App\Jobs;

use App\Models\BrasileiraoContest;
use App\Models\BrasileiraoInstitution;
use App\Models\BrasileiraoWeeklyChallenge;
use App\Services\BrasileiraoLeaderboardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class RecalculateBrasileiraoScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(BrasileiraoLeaderboardService $service): void
    {
        DB::transaction(function () use ($service) {
            BrasileiraoInstitution::query()->update([
                'bixos_points'       => 0,
                'bixos_solutions'    => 0,
                'veterans_points'    => 0,
                'veterans_solutions' => 0,
            ]);

            BrasileiraoWeeklyChallenge::query()->update(['scored_at' => null]);
            BrasileiraoContest::query()->update(['scored_at' => null]);

            $challenges = BrasileiraoWeeklyChallenge::where('end_at', '<=', now())->get();
            foreach ($challenges as $challenge) {
                $leaguesToScore = match ($challenge->league) {
                    'bixos'     => ['bixos'],
                    'veteranos' => ['veteranos'],
                    default     => ['bixos', 'veteranos'],
                };

                foreach ($leaguesToScore as $targetLeague) {
                    $service->applyChallengeScore($challenge, $targetLeague);
                }

                $challenge->update(['scored_at' => now()]);
            }

            $brasilContests = BrasileiraoContest::with('contest')->get();
            foreach ($brasilContests as $bc) {
                if (! $bc->contest || ! $bc->contest->endTime()->lte(now())) {
                    continue;
                }

                $leaguesToScore = match ($bc->league) {
                    'bixos'     => ['bixos'],
                    'veteranos' => ['veteranos'],
                    default     => ['bixos', 'veteranos'],
                };

                foreach ($leaguesToScore as $targetLeague) {
                    $service->applyContestScore($bc, $targetLeague);
                }

                $bc->update(['scored_at' => now()]);
            }
        });
    }
}
