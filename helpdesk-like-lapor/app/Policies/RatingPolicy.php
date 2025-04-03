<?php

namespace App\Policies;

use App\Models\Agency; // Import Agency
use App\Models\Complaint; // Import Complaint
use App\Models\Rating;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RatingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any ratings.
     * Maybe Admin can see all ratings? Or view per agency?
     * Let's restrict general listing for now. View via Agency or Complaint.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'Admin'; // Only Admin can list all ratings directly
    }

    /**
     * Determine whether the user can view the model.
     * Admin, the user who gave the rating, or manager of the rated agency?
     */
    public function view(User $user, Rating $rating): bool
    {
        if ($user->role === 'Admin') return true;
        // User who gave the rating
        if ($user->id === $rating->user_id) return true;
        // Manager of the rated agency
        if ($user->role === 'Agency Manager' && $user->agency_id === $rating->agency_id) return true;

        return false;
    }

    /**
     * Determine whether the user can create ratings.
     * Only Reporters can rate, possibly tied to a specific resolved complaint.
     */
    public function create(User $user): bool // We'll add complaint check in request/controller
    {
        // Basic check: Only reporters can initiate rating creation
        return $user->role === 'Reporter';
    }

     /**
      * Determine whether the user can rate a specific COMPLAINT.
      * Custom policy method used in the request/controller.
      */
     public function rateComplaint(User $user, Complaint $complaint): bool
     {
         // 1. User must be the reporter who filed the complaint
         if ($user->id !== $complaint->user_id) {
             return false;
         }
         // 2. Complaint must be resolved
         if ($complaint->status !== 'Resolved') {
             return false;
         }
         // 3. Complaint must have an assigned agency to rate
         if ($complaint->agency_id === null) {
             return false;
         }
         // 4. User should not have already rated this specific complaint/agency interaction
         $existingRating = Rating::where('user_id', $user->id)
                                 ->where('complaint_id', $complaint->id)
                                 // ->where('agency_id', $complaint->agency_id) // Optional: check agency too
                                 ->exists();
         return !$existingRating;
     }


    /**
     * Determine whether the user can update the rating. (Generally no)
     */
    public function update(User $user, Rating $rating): bool
    {
        return false; // Disallow user updates
    }

    /**
     * Determine whether the user can delete the rating. (Maybe Admin only)
     */
    public function delete(User $user, Rating $rating): bool
    {
        return $user->role === 'Admin'; // Only Admin can delete ratings
    }

    // restore/forceDelete if needed
}