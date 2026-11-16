<?php

namespace App\Policies;

use App\Models\User;

/**
 * Admin accounts are not managed in the admin: the owner's is the only one (AS-4), and she changes
 * her own name, email, password and two-step sign-in from her profile.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, User $account): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $account): bool
    {
        return false;
    }

    public function delete(User $user, User $account): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
