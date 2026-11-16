<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/** Creates the store owner's admin account on a new server. The owner sets up two-step verification at first login. */
#[Signature('jude:create-owner')]
#[Description('Create the owner account for the store admin')]
class CreateOwner extends Command
{
    public function handle(): int
    {
        if (User::query()->where('role', User::OWNER)->exists()) {
            $this->error('An owner account already exists.');

            return self::FAILURE;
        }

        $data = [
            'name' => text('Name', required: true),
            'email' => text('Email', required: true),
            'password' => password('Password (at least 12 characters)', required: true),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(12)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $owner = new User($data);
        $owner->role = User::OWNER;
        $owner->email_verified_at = now();
        $owner->save();

        $this->info("Owner account created for {$owner->email}.");

        return self::SUCCESS;
    }
}
