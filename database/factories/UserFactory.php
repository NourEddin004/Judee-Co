<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    public const TEST_TWO_STEP_SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'staff',                  // an account that cannot sign in; owner() makes the owner
            'remember_token' => Str::random(10),
        ];
    }

    /** The owner, with two-step verification already set up (REQ-USR-03). */
    public function owner(): static
    {
        return $this->state(fn () => [
            'role' => 'owner',
            'two_factor_secret' => self::TEST_TWO_STEP_SECRET,
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function withoutTwoStep(): static
    {
        return $this->state(fn () => ['two_factor_secret' => null, 'two_factor_confirmed_at' => null]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
