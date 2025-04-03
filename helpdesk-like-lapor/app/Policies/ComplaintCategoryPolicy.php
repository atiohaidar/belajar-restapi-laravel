<?php

namespace App\Policies;

use App\Models\ComplaintCategory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ComplaintCategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     * Admins and Managers can view categories.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'Admin' || $user->role === 'Agency Manager';
    }

    /**
     * Determine whether the user can view the model.
     * Admins and Managers can view a specific category.
     */
    public function view(User $user, ComplaintCategory $complaintCategory): bool
    {
        return $user->role === 'Admin' || $user->role === 'Agency Manager';
    }

    /**
     * Determine whether the user can create models.
     * Only Admins can create categories.
     */
    public function create(User $user): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can update the model.
     * Only Admins can update categories.
     */
    public function update(User $user, ComplaintCategory $complaintCategory): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can delete the model.
     * Only Admins can delete categories.
     */
    public function delete(User $user, ComplaintCategory $complaintCategory): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can restore the model. (If using SoftDeletes)
     */
    // public function restore(User $user, ComplaintCategory $complaintCategory): bool
    // {
    //     return $user->role === 'Admin';
    // }

    /**
     * Determine whether the user can permanently delete the model. (If using SoftDeletes)
     */
    // public function forceDelete(User $user, ComplaintCategory $complaintCategory): bool
    // {
    //     return $user->role === 'Admin';
    // }
}