<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;


class QuestionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any questions.
     * Recruiters, Authors, Admins can list questions.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'recruiter', 'author']);
    }

    /**
     * Determine whether the user can view a specific question.
     * Must be same organization and have role admin/recruiter/author.
     */
    public function view(User $user, Question $question): bool
    {
        return $user->organization_id === $question->organization_id
            && in_array($user->role, ['admin', 'recruiter', 'author']);
    }

    /**
     * Determine whether the user can create questions.
     * Only Admin and Author.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'author']);
    }

    /**
     * Determine whether the user can update a question.
     * Admin and Author (same organization).
     */
    public function update(User $user, Question $question): bool
    {
        return $user->organization_id === $question->organization_id
            && in_array($user->role, ['admin', 'author']);
    }

    /**
     * Determine whether the user can delete a question.
     * Only Admin (soft delete).
     */
    public function delete(User $user, Question $question): bool
    {
        return $user->organization_id === $question->organization_id
            && $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore a question.
     */
    public function restore(User $user, Question $question): bool
    {
        return $user->organization_id === $question->organization_id
            && $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete a question.
     */
    public function forceDelete(User $user, Question $question): bool
    {
        return $user->organization_id === $question->organization_id
            && $user->role === 'admin';
    }
}


// class QuestionPolicy
// {
//     /**
//      * Determine whether the user can view any models.
//      */
//     public function viewAny(User $user): bool
//     {
//         return false;
//     }

//     /**
//      * Determine whether the user can view the model.
//      */
//     public function view(User $user, Question $question): bool
//     {
//         return false;
//     }

//     /**
//      * Determine whether the user can create models.
//      */
//     public function create(User $user): bool
//     {
//         return false;
//     }

//     /**
//      * Determine whether the user can update the model.
//      */
//     public function update(User $user, Question $question): bool
//     {
//         return false;
//     }

//     /**
//      * Determine whether the user can delete the model.
//      */
//     public function delete(User $user, Question $question): bool
//     {
//         return false;
//     }

//     /**
//      * Determine whether the user can restore the model.
//      */
//     public function restore(User $user, Question $question): bool
//     {
//         return false;
//     }

//     /**
//      * Determine whether the user can permanently delete the model.
//      */
//     public function forceDelete(User $user, Question $question): bool
//     {
//         return false;
//     }
// }
