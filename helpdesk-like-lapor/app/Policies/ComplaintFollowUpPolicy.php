<?php

namespace App\Policies;

use App\Models\Complaint; // Import Complaint
use App\Models\ComplaintFollowUp;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintFollowUpPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * Viewing follow-ups is tied to viewing the parent complaint.
     */
    // public function viewAny(User $user): bool { return false; }

    /**
     * Determine whether the user can view the model.
     * Viewing follow-ups is tied to viewing the parent complaint.
     */
    // public function view(User $user, ComplaintFollowUp $complaintFollowUp): bool { return false; }

    /**
     * Determine whether the user can create models.
     * Check if user can update the associated complaint (proxy for permission).
     */
    public function create(User $user, Complaint $complaint): bool // Authorize based on the Complaint
    {
        // Only Admin or the Manager of the assigned agency can add follow-ups
        if ($user->role === 'Admin') {
            return true;
        }
        if ($user->role === 'Agency Manager' && $complaint->agency_id === $user->agency_id) {
             // Allow follow-up even if resolved? Maybe, for post-resolution notes. Not if archived?
             return $complaint->status !== 'Archived';
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     * Follow-ups are typically immutable records.
     */
    public function update(User $user, ComplaintFollowUp $complaintFollowUp): bool
    {
        return false; // Disallow editing follow-ups
    }

    /**
     * Determine whether the user can delete the model.
     * Maybe allow Admin to delete erroneous follow-ups? Or immutable? Let's say Admin only.
     */
    public function delete(User $user): bool
    {
        return $user->role === 'Admin';
    }

    // restore/forceDelete if needed
}