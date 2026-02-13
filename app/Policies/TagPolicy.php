<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;

    /**
     * View any tags – all authenticated users (Admin, Recruiter, Author, Candidate?).
     * Tags are global and read-only for non-admin/author.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'recruiter', 'author']);
    }

    /**
     * View a tag – anyone can view.
     */
    public function view(User $user, Tag $tag): bool
    {
        return true;
    }

    /**
     * Create tag – Admin and Author.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'author']);
    }

    /**
     * Update tag – Admin and Author.
     */
    public function update(User $user, Tag $tag): bool
    {
        return in_array($user->role, ['admin', 'author']);
    }

    /**
     * Delete tag – Admin only.
     */
    public function delete(User $user, Tag $tag): bool
    {
        return $user->role === 'admin';
    }
}
