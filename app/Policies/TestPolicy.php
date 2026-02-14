<?php

namespace App\Policies;

use App\Models\Test;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TestPolicy
{
    use HandlesAuthorization;

    /**
     * View any tests - Admin, Recruiter, and Author.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'recruiter', 'author'], true);
    }

    /**
     * View a specific test - same organization and supported role.
     */
    public function view(User $user, Test $test): bool
    {
        return $user->organization_id === $test->organization_id
            && in_array($user->role, ['admin', 'recruiter', 'author'], true);
    }

    /**
     * Create test - Admin, Recruiter, and Author.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'recruiter', 'author'], true);
    }

    /**
     * Update test - Admin, Recruiter, and Author (same org).
     */
    public function update(User $user, Test $test): bool
    {
        return $user->organization_id === $test->organization_id
            && in_array($user->role, ['admin', 'recruiter', 'author'], true);
    }

    /**
     * Delete test - Admin only.
     */
    public function delete(User $user, Test $test): bool
    {
        return $user->organization_id === $test->organization_id
            && $user->role === 'admin';
    }

    /**
     * Publish/archive test - Admin, Recruiter, and Author.
     */
    public function publish(User $user, Test $test): bool
    {
        return $user->organization_id === $test->organization_id
            && in_array($user->role, ['admin', 'recruiter', 'author'], true);
    }

    /**
     * Restore test - Admin only.
     */
    public function restore(User $user, Test $test): bool
    {
        return $user->organization_id === $test->organization_id
            && $user->role === 'admin';
    }

    /**
     * Force delete test - Admin only.
     */
    public function forceDelete(User $user, Test $test): bool
    {
        return $user->organization_id === $test->organization_id
            && $user->role === 'admin';
    }
}
