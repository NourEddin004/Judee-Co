<?php

namespace App\Policies;

use App\Models\User;

/** The owner can read the activity log; nobody can change it. */
class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user): bool
    {
        return $user->isOwner();
    }
}
