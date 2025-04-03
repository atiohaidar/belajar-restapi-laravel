<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization; // Use HandlesAuthorization trait

class UserPolicy
{
    use HandlesAuthorization; // Add this trait

    /**
     * Determine whether the user can view any models.
     * Only Admins can list all users.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can view the model.
     * Only Admins can view any user profile via this specific endpoint.
     * (Users might view their own profile via a different endpoint/policy method later)
     */
    public function view(User $user, User $model): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can create models.
     * Only Admins can create new users.
     */
    public function create(User $user): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can update the model.
     * Only Admins can update other users.
     * Disallow admin from updating their own role/agency via this endpoint for safety.
     */
    public function update(User $user, User $model): bool
    {
        // Admin can update any user profile
        return $user->role === 'Admin';

        // Optional: More granular control (prevent self-role change)
        // if ($user->id === $model->id) {
        //     // Check if attempting to change sensitive fields like role on self
        //     // This logic might be better placed in the controller or request validation
        //     return false; // Or return true if self-update of non-sensitive fields is okay
        // }
        // return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can delete the model.
     * Only Admins can delete users, but not themselves.
     */
    public function delete(User $user, User $model): bool
    {
        // Admin cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, User $model): bool
    // {
    //     return $user->role === 'Admin';
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, User $model): bool
    // {
    //      if ($user->id === $model->id) {
    //          return false;
    //      }
    //     return $user->role === 'Admin';
    // }
}