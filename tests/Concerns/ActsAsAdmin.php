<?php

namespace Tests\Concerns;

use App\Models\User;
use Filament\Facades\Filament;

trait ActsAsAdmin
{
    protected function owner(): User
    {
        return User::query()->where('role', User::OWNER)->first() ?? User::factory()->owner()->create(['name' => 'Jude']);
    }

    /** A staff account left from before version 1.2, which can no longer open the admin. */
    protected function formerStaff(): User
    {
        return User::factory()->create(['name' => 'Sara', 'role' => 'staff']);
    }

    protected function signIn(User $user): User
    {
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    protected function asOwner(): User
    {
        return $this->signIn($this->owner());
    }
}
