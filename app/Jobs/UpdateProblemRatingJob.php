<?php

namespace App\Jobs;

use App\Models\Problem;
use App\Models\Rating;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateProblemRatingJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('low');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Rating::where('computed', false)->select('problem_id')->distinct()->get()->each(function ($rating) {
            /** @var Problem */
            $problem = Problem::find($rating->problem_id);
            $problem->rating = $problem->ratings()->avg('value');
            $problem->save();
        });
        Rating::where('computed', false)->update(['computed' => true]);
    }
}
