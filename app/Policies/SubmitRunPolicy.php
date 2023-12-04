<?php

namespace App\Policies;

use App\Models\SubmitRun;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubmitRunPolicy
{
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
        return $user->id==$submitRun->user_id || $user->isAdmin();
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
        if(!$submitRun->file_id)return false;
        return $user->id==$submitRun->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SubmitRun $submitRun): bool
    {
        return $user->isAdmin();
    }

}
