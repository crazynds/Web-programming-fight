<?php

namespace App\Jobs;

use App\Enums\SubmitResult;
use App\Models\Rank;
use App\Models\SubmitRun;
use App\Services\ExecutorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScoreSubmitJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected SubmitRun $submit
    ) {
        //
    }

    public function uniqueId(): string
    {
        return $this->submit->id;
    }


    /**
     * Execute the job.
     */
    public function handle(ExecutorService $executor): void
    {
        $file = $this->submit->file;
        $result = $executor->buildProgram($file, $this->submit->language);
        $problem = $this->submit->problem;
        if ($result == SubmitResult::NoResult) {
            foreach ($problem->scorers as $scorer) {
                $executor->loadFile($scorer->input_id, 'problems/input');
                $executor->execute($scorer->time_limit, $scorer->memory_limit);
                if ($executor->execution_memory > $this->submit->problem->memory_limit + 3 || $executor->execution_time > $this->submit->problem->time_limit) {
                    continue;
                }
                $result = $executor->executeScorer($scorer);
                if ($result) {
                    $categories = null;
                    foreach ($result as $category => $value) {
                        if ($categories == null) $categories = $category;
                        else $categories .= ", " . $category;
                        Rank::upsert([
                            'problem_id' => $problem->id,
                            'submit_run_id' => $this->submit->id,
                            'category' => $category,
                            'value' => $value,
                        ], uniqueBy: ['problem_id', 'category', 'submit_run_id'], update: ['value']);
                    }
                    if (strlen($scorer->categories) < strlen($categories)) {
                        $scorer->categories = $categories;
                        $scorer->save();
                    }
                }
            }
        }
    }
}
