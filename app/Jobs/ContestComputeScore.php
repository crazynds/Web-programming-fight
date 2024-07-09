<?php

namespace App\Jobs;

use App\Enums\SubmitResult;
use App\Models\Competitor;
use App\Models\CompetitorScore;
use App\Models\Contest;
use App\Models\SubmitRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ContestComputeScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected SubmitRun $submitRun,
        protected Contest $contest,
        protected Competitor $competitor
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        switch ($this->submitRun->result) {
            case SubmitResult::fromValue(SubmitResult::Accepted)->description:
                $computedPontuation = $this->calculateScore();

                /** @var CompetitorScore */
                $score = $this->competitor->scores()->where('problem_id', $this->submitRun->problem_id)->first();

                if (!$score) {
                    $this->competitor->scores()->create([
                        'problem_id' => $this->submitRun->problem_id,
                        'submit_run_id' => $this->submitRun->id,
                        'penality' => $this->competitor->penality,
                        'score' => $computedPontuation,
                    ]);
                } else if ($score->score < $computedPontuation) {
                    $score->update([
                        'score' => $computedPontuation,
                        'penality' => $this->competitor->penality,
                        'submit_run_id' => $this->submitRun->id
                    ]);
                }
                break;

            case SubmitResult::fromValue(SubmitResult::TimeLimit)->description:
            case SubmitResult::fromValue(SubmitResult::MemoryLimit)->description:
            case SubmitResult::fromValue(SubmitResult::RuntimeError)->description:
            case SubmitResult::fromValue(SubmitResult::WrongAnswer)->description:
                if ($this->contest->parcial_solution && false) {
                    // TODO: COMPUTE IF IS PARCIAL SOLUTION
                } else {
                    $this->competitor->penality += $this->contest->penality;
                    $this->competitor->save();
                }
                break;
            case SubmitResult::fromValue(SubmitResult::CompilationError)->description:
                // increase penality
                $this->competitor->penality += $this->contest->penality;
                $this->competitor->save();
                break;
            default:
                // Do nothing
        }
    }


    private function calculateScore($percent = 1): int
    {
        $score = 1;

        if ($this->contest->time_based_points || $this->contest->parcial_solution)
            $score *= 1000;               // Multiply by 1000

        if ($this->contest->time_based_points) {
            /** @var Carbon */
            $startTime = $this->contest->start_time;
            /** @var Carbon */
            $sendTime = $this->submitRun->created_at;
            $diff = $startTime->addMinutes($this->contest->duration)->diffInMinutes($sendTime) * -1;
            $p = $diff / $this->contest->duration;
            $score *= 0.7 + 0.3 * $p;    //100%-70%
        }
        return ceil($score * $percent);
    }
}
