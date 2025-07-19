<?php

namespace App\Http\Controllers;

use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Jobs\ContestComputeScore;
use App\Models\Contest;
use App\Models\Submission;

class AdminJudgeSubmissionController extends Controller
{
    public function accept(Contest $contest, Submission $submission)
    {
        $this->authorize('admin', $contest);
        $submission->update([
            'status' => SubmitStatus::Judged,
        ]);
        if ($submission->contest) {
            ContestComputeScore::dispatchSync($submission, $submission->contest, $submission->competitor);
        }

        return redirect()->back();
    }

    public function rejectWA(Contest $contest, Submission $submission)
    {
        abort_if($submission->contest_id != $contest->id, 403);
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
        abort_if($submission->contest_id != $contest->id, 403);
        $this->authorize('admin', $contest);
        $submission->update([
            'status' => SubmitStatus::Judged,
            'result' => SubmitResult::TimeLimit,
        ]);
        if ($submission->contest) {
            ContestComputeScore::dispatchSync($submission, $submission->contest, $submission->competitor);
        }

        return redirect()->back();
    }

    public function rejectAI(Contest $contest, Submission $submission)
    {
        abort_if($submission->contest_id != $contest->id, 403);
        $this->authorize('admin', $contest);
        $submission->update([
            'status' => SubmitStatus::Judged,
            'result' => SubmitResult::AIDetected,
        ]);
        if ($submission->contest) {
            ContestComputeScore::dispatchSync($submission, $submission->contest, $submission->competitor);
        }

        return redirect()->back();
    }
}
