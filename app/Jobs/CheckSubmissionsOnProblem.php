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
        $this->onQueue('low');
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
        foreach ($this->problem->submissions()->whereNotNull('contest_id')->lazy() as $run) {
            // Para cada submição que deu accepted mas não bate o número de testes passados com a quantidade de testes validados
            ExecuteSubmitJob::dispatch($run)->onQueue('contest');
        }
        foreach ($this->problem->submissions()->whereIn('result', [
            SubmitResult::Accepted,
            SubmitResult::WrongAnswer,
        ])->lazy() as $run) {
            // Para cada submição que deu accepted mas não bate o número de testes passados com a quantidade de testes validados
            ExecuteSubmitJob::dispatch($run)->onQueue('low');
        }
    }
}
