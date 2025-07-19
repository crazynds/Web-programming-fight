<?php

namespace App\Observers;

use App\Models\Scorer;

class ScorerObserver
{
    /**
     * Handle the Submission "deleted" event.
     */
    public function deleted(Scorer $scorer): void
    {
        if ((bool) $scorer->file_id) {
            $scorer->file->delete();
        }
        if ((bool) $scorer->input_id) {
            $scorer->input->delete();
        }
    }
}
