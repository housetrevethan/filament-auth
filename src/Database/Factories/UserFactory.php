<?php

namespace Housetrevethan\FilamentAuth\Database\Factories;

use Housetrevethan\FilamentAuth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'remember_token'    => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => \Housetrevethan\FilamentAuth\Enums\UserRole::Admin,
        ]);
    }

    public function core(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => \Housetrevethan\FilamentAuth\Enums\UserRole::Core,
        ]);
    }

    public function client(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => \Housetrevethan\FilamentAuth\Enums\UserRole::Client,
        ]);
    }

    public function oauthMicrosoft(): static
    {
        return $this->state(fn (array $attributes) => [
            'oauth_provider_name'    => 'microsoft',
            'oauth_provider_id'      => fake()->uuid(),
            'oauth_provider_user_id' => fake()->uuid(),
        ]);
    }
}
