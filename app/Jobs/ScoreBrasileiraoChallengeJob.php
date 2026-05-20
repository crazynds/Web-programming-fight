<?php

namespace App\Jobs;

use App\Models\BrasileiraoWeeklyChallenge;
use App\Services\BrasileiraoLeaderboardService;
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

    public function handle(BrasileiraoLeaderboardService $service): void
    {
        $challenge = $this->challenge->fresh();

        // Already scored or challenge not ended yet (end_at was extended after dispatch)
        if ($challenge->scored_at !== null || now()->lt($challenge->end_at)) {
            return;
        }

        DB::transaction(function () use ($challenge, $service) {
            // Re-check inside transaction to prevent race conditions
            $challenge = BrasileiraoWeeklyChallenge::lockForUpdate()->find($challenge->id);
            if ($challenge->scored_at !== null) {
                return;
            }

            $leaguesToScore = match ($challenge->league) {
                'bixos'     => ['bixos'],
                'veteranos' => ['veteranos'],
                default     => ['bixos', 'veteranos'],
            };

            foreach ($leaguesToScore as $targetLeague) {
                $service->applyChallengeScore($challenge, $targetLeague);
            }

            $challenge->update(['scored_at' => now()]);
        });
    }
}
