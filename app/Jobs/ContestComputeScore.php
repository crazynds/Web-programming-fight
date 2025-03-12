<?php

namespace App\Jobs;

use App\Enums\SubmitResult;
use App\Models\Competitor;
use App\Models\Contest;
use App\Models\SubmitRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ContestComputeScore extends RecalculateCompetitorScore implements ShouldQueue
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
        parent::__construct($contest, $competitor);
    }

    protected function compute(): void
    {
        switch ($this->submitRun->result) {
            case SubmitResult::fromValue(SubmitResult::Accepted)->description:
                $computedPontuation = $this->calculateScore($this->contest, $this->submitRun);
                $problemsCount = $this->competitor->submissions()
                    ->where('problem_id', $this->submitRun->problem_id)
                    ->whereNotIn('result', $this->ignoreResults)->count();
                $penality = $this->calculatePenality($this->submitRun, $problemsCount - 1);

                $this->updateCompetitorScore($this->submitRun, $computedPontuation, $penality);

                // Clear leaderboard cache
                Cache::forget('contest:leaderboard:'.$this->contest->id);
                break;
            case SubmitResult::fromValue(SubmitResult::TimeLimit)->description:
            case SubmitResult::fromValue(SubmitResult::MemoryLimit)->description:
            case SubmitResult::fromValue(SubmitResult::RuntimeError)->description:
            case SubmitResult::fromValue(SubmitResult::WrongAnswer)->description:
                if ($this->contest->parcial_solution && false) {
                    // TODO: COMPUTE IF IS PARCIAL SOLUTION
                }
                Cache::forget('contest:leaderboard:'.$this->contest->id);
                break;
            case SubmitResult::fromValue(SubmitResult::CompilationError)->description:
            default:
                Cache::forget('contest:leaderboard:'.$this->contest->id);
                break;
        }
    }
}
