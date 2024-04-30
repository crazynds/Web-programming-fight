<?php

namespace App\Jobs;

use App\Enums\SubmitStatus;
use App\Models\SubmitRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FindBrokenSubmissionsAndFixJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach (SubmitRun::where('status', SubmitStatus::WaitingInLine)->lazy() as $submitRun) {
            ExecuteSubmitJob::dispatch($submitRun);
        }
    }
}
