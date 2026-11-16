<?php

namespace App\Policies;

use App\Models\User;

/**
 * Access checks run on the server for every admin request and action, not only by hiding
 * buttons (REQ-SEC-39). Subclasses say who may manage the model.
 */
abstract class AdminPolicy
{
    abstract protected function allowed(User $user): bool;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function view(User $user): bool
    {
        return $this->allowed($user);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user);
    }

    public function update(User $user): bool
    {
        return $this->allowed($user);
    }

    public function delete(User $user): bool
    {
        return $this->allowed($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function replicate(User $user): bool
    {
        return $this->allowed($user);
    }

    public function reorder(User $user): bool
    {
        return $this->allowed($user);
    }
}
