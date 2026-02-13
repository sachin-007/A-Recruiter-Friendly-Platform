<?php

namespace App\Policies;

use App\Models\Attempt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * View report for an attempt – Admin and Recruiter (same org).
     */
    public function view(User $user, Attempt $attempt): bool
    {
        return $user->organization_id === $attempt->test->organization_id
            && in_array($user->role, ['admin', 'recruiter']);
    }

    /**
     * Export report – same as view.
     */
    public function export(User $user, Attempt $attempt): bool
    {
        return $this->view($user, $attempt);
    }
}

