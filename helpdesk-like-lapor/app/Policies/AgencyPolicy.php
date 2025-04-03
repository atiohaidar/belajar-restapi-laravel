<?php

namespace App\Policies;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AgencyPolicy
{
    /**
     * Determine whether the user can view any models.
     * Allow all authenticated users to view the list of agencies.
     */
    public function viewAny(?User $user): bool // Allow even unauthenticated? Or use auth middleware? Let's require auth.
    {
        // return true; // Anyone can see agencies?
        return $user !== null; // Any authenticated user can see the list
    }

    /**
     * Determine whether the user can view the model.
     * Allow all authenticated users to view a specific agency.
     */
    public function view(?User $user, Agency $agency): bool
    {
       return $user !== null;
    }

    /**
     * Determine whether the user can create models.
     * Only Admins can create agencies.
     */
    public function create(User $user): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can update the model.
     * Only Admins can update agencies.
     */
    public function update(User $user, Agency $agency): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can delete the model.
     * Only Admins can delete agencies.
     */
    public function delete(User $user, Agency $agency): bool
    {
        return $user->role === 'Admin';
    }

    // Add restore/forceDelete if using SoftDeletes for Agency model later
}