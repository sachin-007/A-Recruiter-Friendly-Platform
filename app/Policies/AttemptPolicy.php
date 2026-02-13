<?php

namespace App\Policies;

use App\Models\Attempt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttemptPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'recruiter'], true);
    }

    public function view(User $user, Attempt $attempt): bool
    {
        return in_array($user->role, ['admin', 'recruiter'], true)
            && $user->organization_id === $attempt->test->organization_id;
    }

    public function update(User $user, Attempt $attempt): bool
    {
        return false;
    }

    public function submit(User $user, Attempt $attempt): bool
    {
        return false;
    }

    public function grade(User $user, Attempt $attempt): bool
    {
        return in_array($user->role, ['admin', 'recruiter'], true)
            && $user->organization_id === $attempt->test->organization_id;
    }
}
