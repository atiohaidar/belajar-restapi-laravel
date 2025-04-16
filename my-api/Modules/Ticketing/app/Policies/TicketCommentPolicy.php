<?php

namespace Modules\Ticketing\app\Policies;

use App\Models\User;
use Modules\Ticketing\app\Models\TicketComment;
use Modules\Ticketing\app\Models\Ticket;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketCommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any comments.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Ticketing\app\Models\Ticket  $ticket
     * @return bool
     */
    public function viewAny(User $user, Ticket $ticket)
    {
        // Can view comments if user can view the ticket
        return app(TicketPolicy::class)->view($user, $ticket);
    }

    /**
     * Determine whether the user can view the comment.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Ticketing\app\Models\TicketComment  $comment
     * @return bool
     */
    public function view(User $user, TicketComment $comment)
    {
        // Admin can view any comment
        if ($user->hasRole('admin')) {
            return true;
        }

        // User can only view comments on their tickets
        // For private comments, only admins can see them
        if ($comment->is_private && !$user->hasRole('admin')) {
            return false;
        }

        $ticket = $comment->ticket;
        return $ticket->user_id === $user->id;
    }

    /**
     * Determine whether the user can create comments.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Ticketing\app\Models\Ticket  $ticket
     * @return bool
     */
    public function create(User $user, Ticket $ticket)
    {
        // Can comment if ticket is not closed and either:
        // 1. User is an admin
        // 2. User is the ticket creator
        return $ticket->status !== 'closed' && 
               ($user->hasRole('admin') || $ticket->user_id === $user->id);
    }

    /**
     * Determine whether the user can update the comment.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Ticketing\app\Models\TicketComment  $comment
     * @return bool
     */
    public function update(User $user, TicketComment $comment)
    {
        // Admin can update any comment
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Regular users can only update their own comments
        // And only if the ticket is not closed
        return $comment->user_id === $user->id && 
               $comment->ticket->status !== 'closed';
    }

    /**
     * Determine whether the user can delete the comment.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Ticketing\app\Models\TicketComment  $comment
     * @return bool
     */
    public function delete(User $user, TicketComment $comment)
    {
        // Admin can delete any comment, or users can delete their own
        return $user->hasRole('admin') || $comment->user_id === $user->id;
    }
}