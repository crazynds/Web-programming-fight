<?php

namespace App\Observers;

use App\Enums\SubmitStatus;
use App\Events\UpdateSubmissionEvent;
use App\Models\SubmitRun;

class SubmitRunObserver
{
    /**
     * Handle the SubmitRun "created" event.
     */
    public function created(SubmitRun $submitRun): void {}

    /**
     * Handle the SubmitRun "updated" event.
     */
    public function updated(SubmitRun $submitRun): void
    {
        // Only if the status is not ContestPendingAdminAvaliation
        if ($submitRun->status != SubmitStatus::fromValue(SubmitStatus::AwaitingAdminJudge)->description) {
            // Broadcast event to all connected clients
            UpdateSubmissionEvent::dispatch($submitRun);
        }
    }

    /**
     * Handle the SubmitRun "deleted" event.
     */
    public function deleted(SubmitRun $submitRun): void
    {
        if ((bool) $submitRun->file_id) {
            $submitRun->file->delete();
        }
    }

    /**
     * Handle the SubmitRun "restored" event.
     */
    public function restored(SubmitRun $submitRun): void
    {
        //
    }

    /**
     * Handle the SubmitRun "force deleted" event.
     */
    public function forceDeleted(SubmitRun $submitRun): void
    {
        //
    }
}
