<?php

namespace App\Jobs;

use App\Models\BrasileiraoContest;
use App\Services\BrasileiraoLeaderboardService;
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

    public function handle(BrasileiraoLeaderboardService $service): void
    {
        $bc = $this->brasileiraoContest;

        if ($bc->scored_at !== null) {
            return;
        }

        DB::transaction(function () use ($bc, $service) {
            $leaguesToScore = match ($bc->league) {
                'bixos'     => ['bixos'],
                'veteranos' => ['veteranos'],
                default     => ['bixos', 'veteranos'],
            };

            foreach ($leaguesToScore as $targetLeague) {
                $service->applyContestScore($bc, $targetLeague);
            }

            $bc->update(['scored_at' => now()]);
        });
    }
}
