<?php

namespace App\Jobs;

use App\Models\BrasileiraoWeeklyChallenge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishBrasileiraoChallengeProblemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public BrasileiraoWeeklyChallenge $challenge) {}

    public function handle(): void
    {
        $challenge = $this->challenge->fresh();

        if (! $challenge->problem->visible) {
            $challenge->problem->update(['visible' => true]);
        }
    }
}
