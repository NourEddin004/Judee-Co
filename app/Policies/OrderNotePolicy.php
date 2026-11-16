<?php

namespace App\Policies;

use App\Models\User;

/** The owner adds notes to orders (REQ-ORD-20). A note stays as written. */
class OrderNotePolicy
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

    public function create(User $user): bool
    {
        return $this->admin($user);
    }

    public function update(User $user): bool
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
