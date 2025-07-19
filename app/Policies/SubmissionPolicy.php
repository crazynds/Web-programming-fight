<?php

namespace App\Policies;

use App\Enums\SubmitResult;
use App\Models\Submission;
use App\Models\User;
use App\Services\ContestService;

class SubmissionPolicy
{
    public function __construct(protected ContestService $contestService) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Submission $submission): bool
    {
        return $user->id == $submission->user_id || $user->isAdmin();
    }

    public function viewOutput(User $user, Submission $submission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if (! $this->view($user, $submission)) {
            return false;
        }

        $compErr = $submission->result == SubmitResult::fromValue(SubmitResult::CompilationError)->description;
        $runErr = $submission->result == SubmitResult::fromValue(SubmitResult::RuntimeError)->description;

        return $submission->contest_id == null || $compErr || $runErr || $submission->contest->show_wrong_answer;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Submission $submission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($submission->contest_id) {
            return false;
        }
        if (! $submission->file_id) {
            return false;
        }

        return ($user->id == $submission->user_id && $submission->contest_id == null) || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Submission $submission): bool
    {
        return $user->isAdmin();
    }
}
