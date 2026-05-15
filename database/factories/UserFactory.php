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
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
<<<<<<< HEAD
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

=======
            'name'               => fake()->name(),
            'email'              => fake()->unique()->safeEmail(),
            'email_verified_at'  => now(),
            'password'           => static::$password ??= Hash::make('password'),
            'remember_token'     => Str::random(10),
            'role'               => 'sales', // default role for factory-created users
        ];
    }

    /** Sales Rep role state */
    public function sales(): static
    {
        return $this->state(fn () => ['role' => 'sales']);
    }

    /** Admin role state */
    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    /** Super-admin role state */
    public function superAdmin(): static
    {
        return $this->state(fn () => ['role' => 'super-admin']);
    }

    /** No role (simulates missing/corrupt role claim — AC-8) */
    public function noRole(): static
    {
        return $this->state(fn () => ['role' => null]);
    }

>>>>>>> 9ad783d (Initial commit)
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
