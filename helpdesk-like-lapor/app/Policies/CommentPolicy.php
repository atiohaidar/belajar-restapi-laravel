<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * (Not typically needed as comments are viewed via Complaint)
     */
    // public function viewAny(User $user): bool
    // {
    //     return false;
    // }

    /**
     * Determine whether the user can view the model.
     * (Not typically needed as comments are viewed via Complaint)
     */
    // public function view(User $user, Comment $comment): bool
    // {
    //     return false;
    // }

    /**
     * Determine whether the user can create models.
     * (Creation authorization handled by ComplaintPolicy@addComment)
     */
    // public function create(User $user): bool
    // {
    //     return false; // Use ComplaintPolicy@addComment
    // }

    /**
     * Determine whether the user can update the model.
     * Generally, comments are not editable after posting.
     */
    public function update(User $user, Comment $comment): bool
    {
        return false; // Disallow editing comments
    }

    /**
     * Determine whether the user can delete the model.
     * Allow Admin or the user who wrote the comment to delete it.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // Admin can delete any comment
        if ($user->role === 'Admin') {
            return true;
        }

        // User can delete their own comment (maybe within a time limit?)
        return $user->id === $comment->user_id;

        // Add time limit logic if needed:
        // return $user->id === $comment->user_id && $comment->created_at->gt(now()->subMinutes(15));
    }

    // restore/forceDelete if using SoftDeletes for Comment model
}