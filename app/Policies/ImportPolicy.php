<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Import;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportPolicy
{
    use HandlesAuthorization;

    /**
     * View any imports – Admin and Author (who created them).
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'author']);
    }

    /**
     * View a specific import – creator or admin.
     */
    public function view(User $user, Import $import): bool
    {
        return $user->organization_id === $import->organization_id
            && ($user->id === $import->imported_by || $user->role === 'admin');
    }

    /**
     * Create import – Author and Admin.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'author']);
    }

    /**
     * Delete import – creator or admin.
     */
    public function delete(User $user, Import $import): bool
    {
        return $user->organization_id === $import->organization_id
            && ($user->id === $import->imported_by || $user->role === 'admin');
    }
}

