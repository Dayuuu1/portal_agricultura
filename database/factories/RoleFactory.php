<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'description' => fake()->sentence(),
            'permissions' => [],
        ];
    }

    /**
     * @param  array<string, list<string>>  $permissions
     */
    public function withPermissions(array $permissions): static
    {
        return $this->state(fn (): array => ['permissions' => $permissions]);
    }
}
