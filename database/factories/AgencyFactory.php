<?php

namespace Database\Factories;

use App\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agency>
 */
class AgencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Agencia Agraria '.fake()->unique()->city(),
            'province' => 'Tambopata',
            'district' => 'Tambopata',
            'address' => fake()->streetAddress(),
            'phone' => '082 '.fake()->numerify('######'),
            'schedule' => 'Lunes a viernes, 8:00 a.m. a 4:00 p.m.',
            'services' => ['Asistencia técnica', 'Registro de productores'],
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
