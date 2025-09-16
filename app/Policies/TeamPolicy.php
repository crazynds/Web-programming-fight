<?php

namespace App\Policies;

use App\Models\Competitor;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
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
    public function view(User $user, Team $team): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->myTeams()->count() < 10;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        return $user->myTeams()->where('teams.id', $team->id)->exists() && $this->modifyMembers($user, $team);
    }

    public function modifyMembers(User $user, Team $team): bool
    {
        return ! Competitor::where('team_id', $team->id)->whereHas('contest', function ($query) {
            $query->whereRaw('DATE_ADD(start_time, INTERVAL duration MINUTE) > ?', [now()]);
        })->exists();
    }

    public function leave(User $user, Team $team): bool
    {
        return $user->teams()->where('teams.id', $team->id)->exists() && ! $user->myTeams()->where('teams.id', $team->id)->exists() && $this->modifyMembers($user, $team);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        return $this->update($user, $team);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Team $team): bool
    {
        return $user?->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Team $team): bool
    {
        return $user?->isAdmin();
    }
}
