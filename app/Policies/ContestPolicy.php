<?php

namespace App\Policies;

use App\Models\Contest;
use App\Models\User;

class ContestPolicy
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
    public function view(User $user, Contest $contest): bool
    {
        return true;
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
    public function update(User $user, Contest $contest): bool
    {
        return $user->isAdmin() || $user->id == $contest->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contest $contest): bool
    {
        /** @var \Illuminate\Support\Carbon */
        $start = $contest->start_time;
        if ($start->addMinutes($contest->duration)->lessThan(now())) return false;
        return $user->isAdmin() || $user->id == $contest->user_id;
    }
}
