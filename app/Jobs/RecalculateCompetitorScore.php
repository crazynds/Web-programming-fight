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
use Illuminate\Support\Facades\DB;

class RecalculateCompetitorScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ignoreResults = [
        SubmitResult::NoTestCase,
        SubmitResult::Error,
        SubmitResult::FileTooLarge,
        SubmitResult::InvalidUtf8File,
        SubmitResult::LanguageNotSupported,
        SubmitResult::InternalCompilationError
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Contest $contest,
        protected Competitor $competitor
    ) {
        $this->onQueue('contest');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lock = Cache::lock('contest:compute:' . $this->competitor);
        $lock->get();
        $this->compute();
        $lock->release();
    }


    protected function compute(): void
    {
        $submissions = $this->competitor->submissions()->orderBy('id')->lazy();
        $problemsPenality = [];
        DB::beginTransaction();
        $this->competitor->scores()->delete();
        /** @var SubmitRun */
        foreach ($submissions as $submit) {
            if ($submit->result == SubmitResult::fromValue(SubmitResult::Accepted)->description) {
                $computedPontuation = $this->calculateScore($this->contest, $submit);
                $penality = $this->calculatePenality($submit, $problemsPenality[$submit->problem_id] ?? 0);
                $this->updateCompetitorScore($submit, $computedPontuation, $penality);
            } else {
                $ignore = false;
                foreach ($this->ignoreResults as $result) {
                    if ($submit->result == SubmitResult::fromValue($result)->description) {
                        $ignore = true;
                        // Ignore this problem
                        break;
                    }
                }
                if ($ignore)
                    continue;
                $problemsPenality[$submit->problem_id] = ($problemsPenality[$submit->problem_id] ?? 0) + 1;
                // erro no geral
            }
        }
        DB::commit();
        Cache::forget('contest:leaderboard:' . $this->contest->id);
    }

    protected function updateCompetitorScore(SubmitRun $submit, int $computedPontuation, int $penality)
    {
        $score = $this->competitor->scores()->where('problem_id', $submit->problem_id)->first();
        if (!$score) {
            $this->competitor->scores()->create([
                'problem_id' => $submit->problem_id,
                'submit_run_id' => $submit->id,
                'penality' => $penality,
                'score' => $computedPontuation,
            ]);
        } else if ($score->score < $computedPontuation || ($score->score == $computedPontuation && $score->penality > $penality)) {
            $score->update([
                'score' => $computedPontuation,
                'penality' => $penality,
                'submit_run_id' => $submit->id
            ]);
        }
    }

    protected function calculatePenality(SubmitRun $submitRun, int $problemsBefore): int
    {
        $penality = 0;
        if (!($this->contest->time_based_points || $this->contest->parcial_solution)) {
            /** @var Carbon */
            $created = $submitRun->created_at;
            $penality = $created->diffInMinutes($this->contest->start_time);
            $penality = floor(abs($penality));
        }
        $penalityTried = $problemsBefore * $this->contest->penality;
        return $penality + $penalityTried;
    }


    protected function calculateScore(Contest $contest, SubmitRun $submitRun, $percent = 1): int
    {
        $score = 1;

        if ($contest->time_based_points || $contest->parcial_solution)
            $score *= 1000 - $this->competitor->penality;      // Multiply by 1000

        if ($contest->time_based_points) {
            /** @var Carbon */
            $startTime = $contest->start_time;
            /** @var Carbon */
            $sendTime = $submitRun->created_at;
            $diff = $startTime->addMinutes($contest->duration)->diffInMinutes($sendTime) * -1;
            $p = $diff / $contest->duration;
            $score *= 0.7 + 0.3 * $p;    //100%-70%
        }
        return ceil($score * $percent);
    }
}
