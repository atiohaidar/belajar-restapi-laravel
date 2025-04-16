<?php

namespace Modules\Ticketing\app\Policies;

use App\Models\User;
use Modules\Ticketing\app\Models\Ticket;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any tickets.
     * Admins can view all tickets, users can only view their own.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        return true; // Everyone can see tickets list (it will be filtered in controller)
    }

    /**
     * Determine whether the user can view the ticket.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Ticketing\app\Models\Ticket  $ticket
     * @return bool
     */
    public function view(User $user, Ticket $ticket)
    {
        // Admin can view any ticket
        if ($user->hasRole('admin')) {
            return true;
        }

        // User can only view their own ticket
        return $ticket->user_id === $user->id;
    }

    /**
     * Determine whether the user can create tickets.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return true; // Any authenticated user can create tickets
    }

    /**
     * Determine whether the user can update the ticket.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Ticketing\app\Models\Ticket  $ticket
     * @return bool
     */
    public function update(User $user, Ticket $ticket)
    {
        // Admin can update any ticket
        if ($user->hasRole('admin')) {
            return true;
        }

        // User can only update their own ticket and only if it's not closed
        return $ticket->user_id === $user->id && $ticket->status !== 'closed';
    }

    /**
     * Determine whether the user can delete the ticket.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Ticketing\app\Models\Ticket  $ticket
     * @return bool
     */
    public function delete(User $user, Ticket $ticket)
    {
        return $user->hasRole('admin'); // Only admin can delete tickets
    }

    /**
     * Determine whether the user can mark ticket as read/unread.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function manageReadStatus(User $user)
    {
        return $user->hasRole('admin');
    }
}