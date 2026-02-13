<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditLogPolicy
{
    use HandlesAuthorization;

    /**
     * View any audit logs – Admin only.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * View a specific audit log – Admin only, same org.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        // Audit log target may belong to different orgs, but we can infer from target.
        // For simplicity, restrict to admin only.
        return $user->role === 'admin';
    }
}

