<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    /**
     * View organization settings – Admin only.
     */
    public function view(User $user, Organization $organization): bool
    {
        return $user->role === 'admin'
            && $user->organization_id === $organization->id;
    }

    /**
     * Update organization settings – Admin only.
     */
    public function update(User $user, Organization $organization): bool
    {
        return $user->role === 'admin'
            && $user->organization_id === $organization->id;
    }

    /**
     * View any organizations – not applicable, users only see their own.
     */
    public function viewAny(User $user): bool
    {
        return false; // Not needed
    }
}

