<?php

namespace Database\Seeders;

use App\Models\Slide;
use Illuminate\Database\Seeder;

class SlideSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            ['Impulsamos el agro amazónico de Madre de Dios', 'Asistencia técnica, información agraria y saneamiento de la propiedad rural para los productores de la región.', 'Conoce la institución', '/institucional/nosotros'],
            ['Convocatorias CAS', 'Revisa los procesos de selección vigentes, sus bases y resultados en un solo lugar.', 'Ver convocatorias', '/convocatorias'],
            ['Transparencia y documentos de gestión', 'Consulta resoluciones, TUPA, ROF, POI y el Cuadro Multianual de Necesidades.', 'Ir a documentos', '/documentos'],
        ];

        foreach ($slides as $order => [$title, $subtitle, $buttonText, $buttonUrl]) {
            Slide::query()->updateOrCreate(
                ['title' => $title],
                ['subtitle' => $subtitle, 'button_text' => $buttonText, 'button_url' => $buttonUrl, 'sort_order' => $order, 'is_active' => true],
            );
        }
    }
}
