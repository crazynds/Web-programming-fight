<?php

namespace App\Jobs;

use App\Enums\SubmitResult;
use App\Models\Problem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckSubmissionsOnProblem implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Problem $problem
    ) {
        $this->onQueue('low')->delay(now()->addMinutes(60));
        //
    }

    public function uniqueId(): string
    {
        return $this->problem->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $testCases_count = $this->problem->testCases()->where('validated', true)->count();
        foreach ($this->problem->submissions()->where('result', SubmitResult::Accepted)->where('num_test_cases', '!=', $testCases_count)->lazy() as $run) {
            // Para cada submição que deu accepted mas não bate o número de testes passados com a quantidade de testes validados
            ExecuteSubmitJob::dispatch($run)->onQueue('submit');
        }
    }
}
