<?php

namespace App\Policies;

use App\Models\User;

/**
 * The owner sees orders, changes their status and adds notes (SRS 3.4). Orders are created only
 * by checkout and are never deleted from the admin.
 */
class OrderPolicy
{
    private function admin(User $user): bool
    {
        return $user->isOwner();
    }

    public function viewAny(User $user): bool
    {
        return $this->admin($user);
    }

    public function view(User $user): bool
    {
        return $this->admin($user);
    }

    public function update(User $user): bool
    {
        return $this->admin($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
