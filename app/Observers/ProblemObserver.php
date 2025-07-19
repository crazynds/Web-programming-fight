<?php

namespace App\Observers;

use App\Models\Problem;

class ProblemObserver
{
    /**
     * Handle the Problem "created" event.
     */
    public function created(Problem $problem): void
    {
        //
    }

    /**
     * Handle the Problem "updated" event.
     */
    public function updated(Problem $problem): void
    {
        //
    }

    /**
     * Handle the Problem "deleted" event.
     */
    public function deleting(Problem $problem): void {}

    /**
     * Handle the Problem "restored" event.
     */
    public function restored(Problem $problem): void
    {
        //
    }

    /**
     * Handle the Submission "force deleted" event.
     */
    public function forceDeleting(Problem $problem): void
    {
        foreach ($problem->submissions()->lazy() as $submit) {
            $submit->delete();
        }
        foreach ($problem->testCases()->lazy() as $testCase) {
            $testCase->delete();
        }
        foreach ($problem->scores()->lazy() as $scorer) {
            $scorer->delete();
        }
        if ($problem->diffProgram) {
            $problem->diffProgram->delete();
        }
    }
}
