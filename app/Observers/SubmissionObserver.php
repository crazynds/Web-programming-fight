<?php

namespace App\Observers;

use App\Enums\SubmitStatus;
use App\Events\UpdateSubmissionEvent;
use App\Models\Submission;

class SubmissionObserver
{
    /**
     * Handle the Submission "created" event.
     */
    public function created(Submission $submission): void {}

    /**
     * Handle the Submission "updated" event.
     */
    public function updated(Submission $submission): void
    {
        // Only if the status is not ContestPendingAdminAvaliation
        if ($submission->status != SubmitStatus::fromValue(SubmitStatus::AwaitingAdminJudge)->description) {
            // Broadcast event to all connected clients
            UpdateSubmissionEvent::dispatch($submission);
        }
    }

    /**
     * Handle the Submission "deleted" event.
     */
    public function deleted(Submission $submission): void
    {
        if ((bool) $submission->file_id) {
            $submission->file->delete();
        }
    }

    /**
     * Handle the Submission "restored" event.
     */
    public function restored(Submission $submission): void
    {
        //
    }

    /**
     * Handle the Submission "force deleted" event.
     */
    public function forceDeleted(Submission $submission): void
    {
        //
    }
}
