<?php

namespace App\Events;

use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Models\SubmitRun;

class UpdateSubmissionTestCaseEvent extends UpdateSubmissionEvent
{

    /**
     * Create a new event instance.
     */
    public function __construct(SubmitRun $submitRun, int $testCase)
    {
        parent::__construct(SubmitRun::findOrFail($submitRun->id));
        $this->data['testCases'] = $testCase;
        $this->data['status'] = SubmitStatus::getDescription(SubmitStatus::Judging);
        $this->data['result'] = SubmitResult::getDescription(SubmitResult::NoResult);
    }

}
