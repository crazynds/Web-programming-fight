<?php

namespace App\Http\Controllers;

use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Jobs\ContestComputeScore;
use App\Jobs\RecalculateCompetitorScore;
use App\Models\Contest;
use App\Models\Submission;

class AdminJudgeSubmissionController extends Controller
{
    public function accept(Contest $contest, Submission $submission)
    {
        $this->authorize('admin', $contest);
        $reCalc = $submission->status == SubmitStatus::getDescription(SubmitStatus::Judged);
        $submission->update([
            'status' => SubmitStatus::Judged,
            'result' => SubmitResult::Accepted,
        ]);
        if ($submission->contest) {
            if ($reCalc) {
                RecalculateCompetitorScore::dispatchSync($submission->contest, $submission->competitor);
            } else {
                ContestComputeScore::dispatchSync($submission, $submission->contest, $submission->competitor);
            }
        }

        return redirect()->back();
    }

    public function rejectWA(Contest $contest, Submission $submission)
    {
        $this->authorize('admin', $contest);
        $submission->update([
            'status' => SubmitStatus::Judged,
            'result' => SubmitResult::WrongAnswer,
        ]);
        if ($submission->contest) {
            ContestComputeScore::dispatchSync($submission, $submission->contest, $submission->competitor);
        }

        return redirect()->back();
    }

    public function rejectTL(Contest $contest, Submission $submission)
    {
        $this->authorize('admin', $contest);
        $submission->update([
            'status' => SubmitStatus::Judged,
            'result' => SubmitResult::TimeLimit,
            'execution_time' => $submission->problem->time_limit,
        ]);
        if ($submission->contest) {
            ContestComputeScore::dispatchSync($submission, $submission->contest, $submission->competitor);
        }

        return redirect()->back();
    }

    public function rejectAI(Contest $contest, Submission $submission)
    {
        $this->authorize('admin', $contest);
        $reCalc = $submission->status == SubmitStatus::getDescription(SubmitStatus::Judged);
        $submission->update([
            'status' => SubmitStatus::Judged,
            'result' => SubmitResult::AiDetected,
        ]);
        if ($submission->contest) {
            if ($reCalc) {
                RecalculateCompetitorScore::dispatchSync($submission->contest, $submission->competitor);
            } else {
                ContestComputeScore::dispatchSync($submission, $submission->contest, $submission->competitor);
            }
        }

        return redirect()->back();
    }
}
