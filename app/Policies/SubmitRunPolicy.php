<?php

namespace App\Policies;

use App\Enums\SubmitResult;
use App\Models\SubmitRun;
use App\Models\User;
use App\Services\ContestService;
use Illuminate\Auth\Access\Response;

class SubmitRunPolicy
{

    public function __construct(protected ContestService $contestService)
    {
    }

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
    public function view(User $user, SubmitRun $submitRun): bool
    {
        return $user->id == $submitRun->user_id || $user->isAdmin();
    }

    public function viewOutput(User $user, SubmitRun $submitRun): bool
    {
        if ($user->isAdmin()) return true;
        if (!$this->view($user, $submitRun)) return false;

        $compErr = $submitRun->result == SubmitResult::fromValue(SubmitResult::CompilationError)->description;
        $runErr = $submitRun->result == SubmitResult::fromValue(SubmitResult::RuntimeError)->description;

        return $submitRun->contest_id == null || $compErr || $runErr || $submitRun->contest->show_wrong_answer;
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
    public function update(User $user, SubmitRun $submitRun): bool
    {
        if ($user->isAdmin()) return true;
        if ($submitRun->contest_id) return false;
        if (!$submitRun->file_id) return false;
        return ($user->id == $submitRun->user_id && $submitRun->contest_id == null) || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SubmitRun $submitRun): bool
    {
        return $user->isAdmin();
    }
}
