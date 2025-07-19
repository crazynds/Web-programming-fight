<?php

namespace App\Policies;

use App\Models\TestCase;
use App\Models\User;

class TestCasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TestCase $testCase): bool
    {
        return $testCase->public || $testCase->problem->user_id == $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TestCase $testCase): bool
    {
        return $testCase->problem->user_id == $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TestCase $testCase): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TestCase $testCase): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TestCase $testCase): bool
    {
        return false;
    }
}
