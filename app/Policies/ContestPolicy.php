<?php

namespace App\Policies;

use App\Models\Contest;
use App\Models\User;

class ContestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Contest $contest): bool
    {
        return true;
    }

    public function enter(User $user, Contest $contest): bool
    {
        return $contest->getCompetitor($user) != null;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->problems()->count() > 1;
    }

    public function admin(User $user, Contest $contest): bool
    {
        return $user->id == $contest->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function viewSubmissions(User $user, Contest $contest): bool
    {
        return $contest->public || $contest->getCompetitor($user) != null || $this->admin($user, $contest);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Contest $contest): bool
    {
        return $this->admin($user, $contest) && $contest->start_time->gt(now());
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contest $contest): bool
    {
        return $this->update($user, $contest);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Contest $contest): bool
    {
        return $user->isAdmin() || $user->id == $contest->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Contest $contest): bool
    {
        return $user->isAdmin();
    }
}
