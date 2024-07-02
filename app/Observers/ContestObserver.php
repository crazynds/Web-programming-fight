<?php

namespace App\Observers;

use App\Models\Contest;

class ContestObserver
{
    /**
     * Handle the Contest "created" event.
     */
    public function creating(Contest $contest): void
    {
    }

    /**
     * Handle the Contest "updated" event.
     */
    public function updating(Contest $contest): void
    {
        if ($contest->isDirty('individual')) {
            $contest->competitors()->delete();
        }
    }

    /**
     * Handle the Contest "deleted" event.
     */
    public function deleted(Contest $contest): void
    {
        //
    }

    /**
     * Handle the Contest "restored" event.
     */
    public function restored(Contest $contest): void
    {
        //
    }

    /**
     * Handle the Contest "force deleted" event.
     */
    public function forceDeleted(Contest $contest): void
    {
        //
    }
}
