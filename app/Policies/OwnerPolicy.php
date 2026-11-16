<?php

namespace App\Policies;

use App\Models\User;

/** Everything managed in the admin: the owner only (C.1, REQ-SEC-39). */
class OwnerPolicy extends AdminPolicy
{
    protected function allowed(User $user): bool
    {
        return $user->isOwner();
    }
}
