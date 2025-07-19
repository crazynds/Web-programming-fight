<?php

namespace App\Events;

use App\Models\Submission;

class UpdateSubmissionEvent extends NewSubmissionEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(Submission $submission)
    {
        parent::__construct($submission);
    }
}
