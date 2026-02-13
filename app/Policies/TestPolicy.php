<?php

namespace App\Policies;

use App\Models\Test;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class TestPolicy
{
    use HandlesAuthorization;

    /**
     * View any tests – Admin, Recruiter, Author.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'recruiter', 'author']);
    }

    /**
     * View a specific test – must be same org and have appropriate role.
     */
    public function view(User $user, Test $test): bool
    {
        return $user->organization_id === $test->organization_id
            && in_array($user->role, ['admin', 'recruiter', 'author']);
    }

    /**
     * Create test – Admin and Author only.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'author']);
    }

    /**
     * Update test – Admin and Author (same org).
     */
    public function update(User $user, Test $test): bool
    {
        return $user->organization_id === $test->organization_id
            && in_array($user->role, ['admin', 'author']);
    }

    /**
     * Delete test – Admin only.
     */
    public function delete(User $user, Test $test): bool
    {
        return $user->organization_id === $test->organization_id
            && $user->role === 'admin';
    }

    /**
     * Publish/archive test – Admin and Author.
     */
    public function publish(User $user, Test $test): bool
    {
        return $user->organization_id === $test->organization_id
            && in_array($user->role, ['admin', 'author']);
    }

    /**
     * Restore test – Admin only.
     */
    public function restore(User $user, Test $test): bool
    {
        return $user->organization_id === $test->organization_id
            && $user->role === 'admin';
    }

    /**
     * Force delete test – Admin only.
     */
    public function forceDelete(User $user, Test $test): bool
    {
        return $user->organization_id === $test->organization_id
            && $user->role === 'admin';
    }
}
