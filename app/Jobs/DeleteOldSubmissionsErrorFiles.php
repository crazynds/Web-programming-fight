<?php

namespace App\Jobs;

use App\Enums\SubmitResult;
use App\Models\SubmitRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteOldSubmissionsErrorFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach (SubmitRun::whereIn('result', [
            SubmitResult::CompilationError,
            SubmitResult::Error,
            SubmitResult::LanguageNotSupported,
            SubmitResult::NoTestCase,
        ])->where('created_at', '<', now()->subMonth())->whereNotNull('file_id')->with('file')->lazy() as $run) {
            $file = $run->file;
            $run->file_id = null;
            $run->save();
            $file->delete();
        }
    }
}
