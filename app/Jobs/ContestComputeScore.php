<?php

namespace App\Jobs;

use App\Models\Competitor;
use App\Models\Contest;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ContestComputeScore extends RecalculateCompetitorScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Submission $submission,
        protected Contest $contest,
        protected Competitor $competitor
    ) {
        parent::__construct($contest, $competitor);
    }

    protected function compute(): void
    {
        $arr = [];
        $this->computeSubmit($this->submission, $arr);
        Cache::forget('contest:leaderboard:'.$this->contest->id);
    }
}
