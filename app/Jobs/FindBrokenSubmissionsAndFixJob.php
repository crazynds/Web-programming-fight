<?php

namespace App\Jobs;

use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FindBrokenSubmissionsAndFixJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $query = Submission::where(function ($query) {
            $query->whereIn('status', [
                SubmitStatus::WaitingInLine,
            ])->orWhereIn('result', [
                SubmitResult::NoResult,
                SubmitResult::NoTestCase,
            ]);
        })->where('created_at', '<', now()->subMinutes(10));
        foreach ($query->lazy() as $submission) {
            ExecuteSubmitJob::dispatch($submission);
        }
    }
}
