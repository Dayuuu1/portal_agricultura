<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public const DEFAULT = 'Noticias institucionales';

    public function run(): void
    {
        $categories = [
            self::DEFAULT => 'Actividades y gestión de la Dirección Regional.',
            'Campañas' => 'Campañas sanitarias, de vacunación y de asistencia técnica.',
            'Ferias y eventos' => 'Ferias agropecuarias, eventos y actividades con productores.',
            'Comunicados' => 'Comunicados oficiales a la ciudadanía.',
        ];

        foreach ($categories as $name => $description) {
            Category::query()->firstOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
