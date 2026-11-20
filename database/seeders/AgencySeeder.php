<?php

namespace Database\Seeders;

use App\Models\Agency;
use Illuminate\Database\Seeder;

class AgencySeeder extends Seeder
{
    public function run(): void
    {
        Agency::query()->updateOrCreate(
            ['name' => 'Agencia Agraria Tambopata'],
            [
                'province' => 'Tambopata',
                'district' => 'Tambopata',
                'schedule' => 'Lunes a viernes de 8:00 a.m. a 4:00 p.m.',
                'description' => '<p>Órgano desconcentrado de la Dirección Regional que brinda atención directa a los productores agrarios de la provincia de Tambopata.</p>',
                'services' => [
                    'Asistencia técnica agropecuaria',
                    'Registro y empadronamiento de productores',
                    'Campañas sanitarias y de vacunación',
                    'Recojo de información estadística agraria',
                    'Orientación sobre saneamiento físico legal',
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
        );
    }
}
