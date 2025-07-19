<?php

namespace App\Events;

use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Models\Submission;

class UpdateSubmissionTestCaseEvent extends UpdateSubmissionEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(Submission $submission, int $testCase)
    {
        parent::__construct(Submission::findOrFail($submission->id));
        $this->data['testCases'] = $testCase;
        $this->data['status'] = SubmitStatus::getDescription(SubmitStatus::Judging);
        $this->data['result'] = SubmitResult::getDescription(SubmitResult::NoResult);
    }
}
