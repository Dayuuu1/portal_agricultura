<?php

namespace Database\Factories;

use App\Models\Role;
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
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['is_admin' => true]);
    }

    /**
     * Gives the user a fresh role holding exactly these permissions.
     *
     * @param  array<string, list<string>>  $permissions  e.g. ['documentos' => ['view', 'create']]
     * @param  list<int>|null  $documentTypeIds  Null allows every document type.
     */
    public function withPermissions(array $permissions, ?array $documentTypeIds = null): static
    {
        return $this->afterCreating(fn (User $user) => $user->roles()->attach(
            Role::factory()->withPermissions($permissions)->create(['document_type_ids' => $documentTypeIds]),
        ));
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
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
