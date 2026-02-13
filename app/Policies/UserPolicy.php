<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * View any users – Admin only.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * View a specific user – Admin only, same org.
     */
    public function view(User $user, User $model): bool
    {
        return $user->role === 'admin'
            && $user->organization_id === $model->organization_id;
    }

    /**
     * Create user – Admin only.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Update user – Admin only, same org.
     */
    public function update(User $user, User $model): bool
    {
        return $user->role === 'admin'
            && $user->organization_id === $model->organization_id;
    }

    /**
     * Delete user – Admin only, same org.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->role === 'admin'
            && $user->organization_id === $model->organization_id
            && $user->id !== $model->id; // cannot delete self
    }

    /**
     * Activate/deactivate – Admin only.
     */
    public function toggleActive(User $user, User $model): bool
    {
        return $this->update($user, $model);
    }

    /**
     * Restore user – Admin only.
     */
    public function restore(User $user, User $model): bool
    {
        return $this->update($user, $model);
    }

    /**
     * Force delete – Admin only.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }
}

