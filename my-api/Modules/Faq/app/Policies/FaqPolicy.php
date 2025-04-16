<?php

namespace Modules\Faq\app\Policies;

use App\Models\User;
use Modules\Faq\app\Models\Faq;
use Illuminate\Auth\Access\HandlesAuthorization;

class FaqPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any FAQs.
     *
     * @param  \App\Models\User|null  $user
     * @return bool
     */
    public function viewAny(?User $user)
    {
        // Public access is allowed for viewing FAQs
        return true;
    }

    /**
     * Determine whether the user can view the FAQ.
     *
     * @param  \App\Models\User|null  $user
     * @param  \Modules\Faq\app\Models\Faq  $faq
     * @return bool
     */
    public function view(?User $user, Faq $faq)
    {
        // Published FAQs can be viewed by anyone
        if ($faq->is_published) {
            return true;
        }

        // Unpublished FAQs can only be viewed by admins or managers
        return $user && ($user->hasRole('admin') || $user->hasRole('manager'));
    }

    /**
     * Determine whether the user can create FAQs.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        // Only admin or manager can create FAQs
        return $user->hasRole('admin') || $user->hasRole('manager');
    }

    /**
     * Determine whether the user can update the FAQ.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Faq\app\Models\Faq  $faq
     * @return bool
     */
    public function update(User $user, Faq $faq)
    {
        // Only admin or manager can update FAQs
        return $user->hasRole('admin') || $user->hasRole('manager');
    }

    /**
     * Determine whether the user can delete the FAQ.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Faq\app\Models\Faq  $faq
     * @return bool
     */
    public function delete(User $user, Faq $faq)
    {
        // Only admin or manager can delete FAQs
        return $user->hasRole('admin') || $user->hasRole('manager');
    }

    /**
     * Determine whether the user can see FAQ categories.
     *
     * @param  \App\Models\User|null  $user
     * @return bool
     */
    public function viewCategories(?User $user)
    {
        // Public access is allowed for viewing FAQ categories
        return true;
    }
}