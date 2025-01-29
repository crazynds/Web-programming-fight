<?php

namespace App\Events;

use App\Models\SubmitRun;

class UpdateSubmissionEvent extends NewSubmissionEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(SubmitRun $submitRun)
    {
        parent::__construct($submitRun);
    }

}
