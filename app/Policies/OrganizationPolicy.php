<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    /**
     * List organizations - super admin only.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * View organization settings.
     */
    public function view(User $user, Organization $organization): bool
    {
        return $user->role === 'admin'
            && $user->organization_id === $organization->id;
    }

    /**
     * Create organizations - super admin only.
     */
    public function create(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * Update organization settings.
     */
    public function update(User $user, Organization $organization): bool
    {
        return $user->role === 'admin'
            && $user->organization_id === $organization->id;
    }
}
