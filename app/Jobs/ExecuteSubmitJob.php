<?php

namespace App\Jobs;

use App\Models\SubmitRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteSubmitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SubmitRun $submit
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        // command
        /**
         *   nsjail -Ml --port 3001 
         *      --user 99999 
         *      --group 99999 
         *      --disable_proc 
         *      --chroot /task 
         *      --time_limit 15 
         *      -R /lib/ 
         *      -R /lib64/ 
         *      -R /usr/bin/ 
         *      /task_exec
         *  
         *  nsjail -Mr --user 99999 --group 99999 --disable_proc -R /lib64/ -R /lib/ --time_limit 15 --max_cpus 2 --disable_clone_newuser /task/run.bin
         */
    }
}
