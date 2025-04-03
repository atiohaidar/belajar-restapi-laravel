<?php

namespace App\Policies;

use App\Models\Agency; // Import Agency
use App\Models\Complaint; // Import Complaint
use App\Models\ComplaintTransfer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintTransferPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view transfer history.
     * Tied to viewing the parent complaint.
     */
    // public function viewAny(User $user): bool { return false; }
    // public function view(User $user, ComplaintTransfer $complaintTransfer): bool { return false; }

    /**
     * Determine whether the user can initiate a transfer for a given complaint.
     */
    public function create(User $user, Complaint $complaint): bool
    {
        // Only Admins can initiate transfers?
        if ($user->role === 'Admin') {
            // Allow transfer unless complaint is resolved/archived?
             return !in_array($complaint->status, ['Resolved', 'Archived']);
        }

        // Or allow Managers to transfer *from* their agency?
        // if ($user->role === 'Agency Manager' && $complaint->agency_id === $user->agency_id) {
        //     return !in_array($complaint->status, ['Resolved', 'Archived']);
        // }

        return false; // For simplicity, let's start with Admin only initiates transfer
    }

    /**
     * Determine whether the user can update a transfer record. (Immutable)
     */
    public function update(User $user, ComplaintTransfer $complaintTransfer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete a transfer record. (Maybe Admin for correction?)
     */
    public function delete(User $user, ComplaintTransfer $complaintTransfer): bool
    {
        return $user->role === 'Admin'; // Allow Admin to delete transfer history if needed
    }

    // restore/forceDelete if needed
}