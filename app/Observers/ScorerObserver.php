<?php

namespace App\Observers;

use App\Models\Scorer;

class ScorerObserver
{
    /**
     * Handle the SubmitRun "deleted" event.
     */
    public function deleted(Scorer $scorer): void
    {
        if (!!$scorer->file_id)
            $scorer->file->delete();
        if (!!$scorer->input_id)
            $scorer->input->delete();
    }
}
