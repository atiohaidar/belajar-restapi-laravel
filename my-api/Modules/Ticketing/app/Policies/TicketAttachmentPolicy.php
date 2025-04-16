<?php

namespace Modules\Ticketing\app\Policies;

use App\Models\User;
use Modules\Ticketing\app\Models\TicketAttachment;
use Modules\Ticketing\app\Models\Ticket;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketAttachmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any attachments.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Ticketing\app\Models\Ticket  $ticket
     * @return bool
     */
    public function viewAny(User $user, Ticket $ticket)
    {
        // Can view attachments if user can view the ticket
        return app(TicketPolicy::class)->view($user, $ticket);
    }

    /**
     * Determine whether the user can view the attachment.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Ticketing\app\Models\TicketAttachment  $attachment
     * @return bool
     */
    public function view(User $user, TicketAttachment $attachment)
    {
        // Admin can view any attachment
        if ($user->hasRole('admin')) {
            return true;
        }

        // User can only view attachments on their tickets
        // For non-public attachments, only admins can see them
        if (!$attachment->is_public && !$user->hasRole('admin')) {
            return false;
        }

        $ticket = $attachment->ticket;
        return $ticket->user_id === $user->id;
    }

    /**
     * Determine whether the user can download the attachment.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Ticketing\app\Models\TicketAttachment  $attachment
     * @return bool
     */
    public function download(User $user, TicketAttachment $attachment)
    {
        // Same rules as viewing
        return $this->view($user, $attachment);
    }

    /**
     * Determine whether the user can create attachments.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Ticketing\app\Models\Ticket  $ticket
     * @return bool
     */
    public function create(User $user, Ticket $ticket)
    {
        // Can attach files if ticket is not closed and either:
        // 1. User is an admin
        // 2. User is the ticket creator
        return $ticket->status !== 'closed' && 
               ($user->hasRole('admin') || $ticket->user_id === $user->id);
    }

    /**
     * Determine whether the user can delete the attachment.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Ticketing\app\Models\TicketAttachment  $attachment
     * @return bool
     */
    public function delete(User $user, TicketAttachment $attachment)
    {
        // Admin can delete any attachment, or users can delete their own
        return $user->hasRole('admin') || $attachment->user_id === $user->id;
    }
}