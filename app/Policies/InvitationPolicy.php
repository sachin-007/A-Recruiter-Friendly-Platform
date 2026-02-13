<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvitationPolicy
{
    use HandlesAuthorization;

    /**
     * View any invitations – Admin and Recruiter (Author may not need).
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'recruiter']);
    }

    /**
     * View a specific invitation – only if user created it or is admin.
     */
    public function view(User $user, Invitation $invitation): bool
    {
        return $user->organization_id === $invitation->test->organization_id
            && ($user->id === $invitation->created_by || $user->role === 'admin');
    }

    /**
     * Create invitation – Recruiter and Admin.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'recruiter']);
    }

    /**
     * Update invitation – only creator or admin.
     */
    public function update(User $user, Invitation $invitation): bool
    {
        return $user->organization_id === $invitation->test->organization_id
            && ($user->id === $invitation->created_by || $user->role === 'admin');
    }

    /**
     * Delete invitation – creator or admin.
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        return $user->organization_id === $invitation->test->organization_id
            && ($user->id === $invitation->created_by || $user->role === 'admin');
    }

    /**
     * Bulk create – same as create.
     */
    public function createBulk(User $user): bool
    {
        return $this->create($user);
    }
}

