<?php

namespace App\Policies;

use App\Models\Problem;
use App\Models\User;
use App\Services\ContestService;
use Illuminate\Auth\Access\Response;

class ProblemPolicy
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
    public function view(User $user, Problem $problem): bool
    {
        return ($this->contestService->inContest && $this->contestService->contest->problems()->where('id', $problem->id)->exists()) ||
            $problem->visible || $this->update($user, $problem);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Problem $problem): bool
    {
        return $user->isAdmin() || $user->id == $problem->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Problem $problem): bool
    {
        return $this->update($user, $problem);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Problem $problem): bool
    {
        return $this->update($user, $problem);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Problem $problem): bool
    {
        return $user->isAdmin();
    }
}
