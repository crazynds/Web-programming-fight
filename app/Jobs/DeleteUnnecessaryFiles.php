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

class DeleteUnnecessaryFiles implements ShouldQueue
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
        foreach(SubmitRun::where('result',SubmitResult::CompilationError)->whereNotNull('file')->with('file')->lazy() as $run){
            $file = $run->file;
            $run->file_id = null;
            dd($file);
            $run->save();
            $file->delete();
        }
    }
}
