<?php

namespace App\Policies;

use App\Models\Attempt;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttemptPolicy
{
    use HandlesAuthorization;

    /**
     * View any attempts – Admin and Recruiter.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'recruiter']);
    }

    /**
     * View a specific attempt.
     * - Admin/Recruiter: any in their org
     * - Candidate: only their own attempt (via token)
     */
    public function view(User $user, Attempt $attempt): bool
    {
        // Admin/Recruiter from same org
        if (in_array($user->role, ['admin', 'recruiter'])) {
            return $user->organization_id === $attempt->test->organization_id;
        }

        // Candidate can view only their own attempt (during test)
        if ($user->role === 'candidate') {
            // The attempt is linked to the candidate via invitation email, not user_id.
            // The candidate is not a registered user, so they have a sanctum token linked to the attempt.
            // For API policy, we need to check if the attempt's tokenable_id matches the authenticated token.
            // We'll handle this via a Gate or directly in controller.
            // Simpler: In controller, we can check if the attempt's invitation token matches the candidate's session.
            // Policy can't easily check that. We'll allow view only via a separate gate.
            return false; // We'll use a separate gate for candidate access.
        }

        return false;
    }

    /**
     * Update attempt – candidate can update their own in-progress attempt.
     * Admin/Recruiter cannot directly update (only review scores later).
     */
    public function update(User $user, Attempt $attempt): bool
    {
        // Candidate: own attempt and not completed
        if ($user->role === 'candidate') {
            // Check if the tokenable is the attempt itself.
            // In controller: $request->user()->tokenable instanceof Attempt
            return $request->user()->tokenable_id === $attempt->id;
        }

        // Admin/Recruiter may need to update scores (manual marking)
        if (in_array($user->role, ['admin', 'recruiter'])) {
            return $user->organization_id === $attempt->test->organization_id;
        }

        return false;
    }

    /**
     * Submit attempt – candidate.
     */
    public function submit(User $user, Attempt $attempt): bool
    {
        return $user->role === 'candidate'
            && $user->tokenable_id === $attempt->id
            && $attempt->status === 'in_progress';
    }

    /**
     * Grade attempt (manual marking) – Admin and Recruiter.
     */
    public function grade(User $user, Attempt $attempt): bool
    {
        return $user->organization_id === $attempt->test->organization_id
            && in_array($user->role, ['admin', 'recruiter']);
    }
}
