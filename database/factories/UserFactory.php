<?php

namespace Database\Factories;

use App\Models\Route as BakeryRoute;
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
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'role' => 'viewer',
            'perms' => null,
            'all_routes' => true,
            'active' => true,
            'is_owner' => false,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
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

    /**
     * The main admin account, which always has full access.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'perms' => User::permissionsForRole('admin'),
            'all_routes' => true,
            'is_owner' => true,
        ]);
    }

    /**
     * An account with the permissions of one of the role presets.
     */
    public function role(string $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
            'perms' => User::permissionsForRole($role),
        ]);
    }

    /**
     * An account the admin has turned off.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * An account that works only on the routes with the given keys.
     *
     * @param  list<string>  $routeKeys
     */
    public function onRoutes(array $routeKeys): static
    {
        return $this->state(fn (array $attributes) => [
            'all_routes' => false,
        ])->afterCreating(function (User $user) use ($routeKeys): void {
            $user->routes()->sync(BakeryRoute::query()->whereIn('source_key', $routeKeys)->pluck('id'));
        });
    }
}
