<?php

namespace App\Policies;

use App\Models\Agency;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintPolicy
{
    use HandlesAuthorization; // Gunakan trait

    /**
     * Determine whether the user can view any models.
     * Logic depends on user role.
     */
    public function viewAny(User $user): bool
    {
        // Anyone logged in can potentially see *some* list (their own, agency's, or all)
        return true;
        // Filtering logic will happen in the controller based on the role.
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Complaint $complaint): bool
    {
        // Admin can view any complaint
        if ($user->role === 'Admin') {
            return true;
        }

        // Reporter can view their own complaint
        if ($user->role === 'Reporter' && $complaint->user_id === $user->id) {
            return true;
        }

        // Agency Manager can view complaints assigned to their agency
        // (Simplification: Only direct agency, not sub-agencies for now)
        if ($user->role === 'Agency Manager' && $complaint->agency_id === $user->agency_id) {
            return true;
        }

        // Add logic for sub-agencies here later if needed

        return false;
    }

    /**
     * Determine whether the user can create models.
     * Only Reporters can create complaints via the standard API endpoint.
     * (Admin might have a separate internal way if needed)
     */
    public function create(User $user): bool
    {
        return $user->role === 'Reporter';
    }

    /**
     * Determine whether the user can update the model.
     * Defines who can *initiate* an update. Specific field changes might be restricted further.
     */
    public function update(User $user, Complaint $complaint): bool
    {
        // Admin can update any complaint (status, priority, assignment, etc.)
        if ($user->role === 'Admin') {
            return true;
        }

        // Agency Manager can update complaints assigned to their agency
        // (Again, simplify to direct agency for now)
        if ($user->role === 'Agency Manager' && $complaint->agency_id === $user->agency_id) {
            // Optionally restrict updates based on complaint status (e.g., cannot update 'Resolved')
            // if (in_array($complaint->status, ['Resolved', 'Archived'])) {
            //     return false;
            // }
            return true;
        }

        // Reporter generally cannot update core fields after creation
        // They can add comments or attachments maybe via different endpoints/policies
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * Let's restrict deletion to Admins for now. Archiving might be better.
     */
    public function delete(User $user, Complaint $complaint): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can add attachments after creation (custom action).
     */
    public function addAttachment(User $user, Complaint $complaint): bool
    {
        // Allow original reporter to add attachments if complaint is not yet resolved/archived?
        if ($user->role === 'Reporter' && $complaint->user_id === $user->id) {
            return !in_array($complaint->status, ['Resolved', 'Archived']);
        }
        // Allow Admin/Manager to add attachments?
        if ($user->role === 'Admin') return true;
        if ($user->role === 'Agency Manager' && $complaint->agency_id === $user->agency_id){
            return !in_array($complaint->status, ['Resolved', 'Archived']);
        }

        return false;
    }

     /**
      * Determine whether the user can add comments.
      */
     public function addComment(User $user, Complaint $complaint): bool
     {
        //  kita engga bisa nge komen yang udah diarsipin
         // Admin can always comment
         if ($user->role === 'Admin') return true;

         // Reporter can comment on their own complaint (if not archived?)
         if ($user->role === 'Reporter' && $complaint->user_id === $user->id) {
             return $complaint->status !== 'Archived';
         }

         // Manager can comment on complaints in their agency (if not archived?)
         if ($user->role === 'Agency Manager' && $complaint->agency_id === $user->agency_id) {
             return $complaint->status !== 'Archived';
         }
         return false;
     }

     // Add policies for follow-ups, transfers, ratings if needed for separate endpoints

    // public function restore(User $user, Complaint $complaint): bool
    // {
    //     return $user->role === 'Admin';
    // }

    // public function forceDelete(User $user, Complaint $complaint): bool
    // {
    //     return $user->role === 'Admin';
    // }
}