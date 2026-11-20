<?php

namespace Database\Factories;

use App\Enums\UnitType;
use App\Models\OrganizationalUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationalUnit>
 */
class OrganizationalUnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Oficina de '.fake()->unique()->words(2, true),
            'type' => UnitType::Apoyo,
            'description' => fake()->sentence(),
            'functions' => '<ul><li>'.fake()->sentence().'</li></ul>',
            'head_name' => fake()->name(),
            'head_title' => 'Jefe(a) de Oficina',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
