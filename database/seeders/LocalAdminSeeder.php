<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** The owner's sign-in account, for a developer machine only. Real servers use `php artisan jude:create-owner`. */
class LocalAdminSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->firstOrNew(['email' => 'owner@jude.test']);
        $owner->forceFill(['name' => 'Jude', 'password' => 'password', 'role' => User::OWNER, 'email_verified_at' => now()])->save();
    }
}
