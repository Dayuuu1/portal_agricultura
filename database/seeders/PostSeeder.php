<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategorySeeder::class);
        $categories = Category::query()->get()->keyBy('name');

        $posts = [
            ['Noticias institucionales', 'La Dirección Regional sostuvo reunión de coordinación con el MEPA', '2026-09-09', true,
                'Representantes de la Dirección Regional de Desarrollo Agropecuario y Riego se reunieron con el MEPA para coordinar acciones conjuntas en favor de los productores de la región.'],
            ['Noticias institucionales', 'GOREMAD inició tratativas con la Embajada del Reino Unido para proyectos de cooperación agraria', '2026-10-07', true,
                'El Gobierno Regional de Madre de Dios inició conversaciones con la Embajada del Reino Unido para desarrollar proyectos de cooperación en el sector agrario, entre otros.'],
            ['Noticias institucionales', 'Autoridades del MIDAGRI y GOREMAD visitaron planta procesadora de copoazú que prepara envío de 25 toneladas a Rusia', '2026-08-21', true,
                'Autoridades del Ministerio de Desarrollo Agrario y Riego y del Gobierno Regional visitaron la planta procesadora de copoazú, donde se prepara un envío de 25 toneladas con destino a Rusia.'],
            ['Campañas', 'En el aniversario de nuestra biodiversidad, GOREMAD y la DRA iniciaron la entrega de bonos del Seguro Agrario Catastrófico', '2026-07-26', false,
                'En el marco del aniversario de la biodiversidad de Madre de Dios, el Gobierno Regional y la Dirección Regional iniciaron la entrega de los bonos del Seguro Agrario Catastrófico a los productores afectados.'],
            ['Campañas', 'Dirección Regional inició campaña gratuita de vacunación contra el carbunco sintomático', '2026-07-10', false,
                'La Dirección Regional inició una campaña gratuita de vacunación del ganado contra el carbunco sintomático en beneficio de los productores pecuarios de la región.'],
            ['Ferias y eventos', 'Con éxito se realizó la feria «A comer pescado» en el campo ferial de Puerto Maldonado', '2026-06-28', false,
                'La feria «A comer pescado» se desarrolló con éxito en el campo ferial de Puerto Maldonado, acercando productos hidrobiológicos de calidad a precios accesibles a la población.'],
            ['Ferias y eventos', 'Feria interregional ofreció productos alimenticios a precios rebajados', '2026-06-14', false,
                'Productores de distintas regiones participaron en la feria interregional, que ofreció productos alimenticios a precios rebajados a la ciudadanía de Madre de Dios.'],
            ['Comunicados', 'Comunicado: publicación de resultados de convocatorias CAS por suplencia 2026', '2026-05-30', false,
                'Se comunica a los postulantes que los resultados finales de las convocatorias CAS por suplencia 2026 se encuentran publicados en la sección Convocatorias del portal.'],
        ];

        foreach ($posts as [$category, $title, $date, $featured, $lead]) {
            Post::query()->updateOrCreate(
                ['title' => $title],
                [
                    'category_id' => $categories[$category]->id,
                    'excerpt' => $lead,
                    'body' => "<p>{$lead}</p><p>La Dirección Regional reafirma su compromiso de seguir trabajando de la mano con los productores agrarios de Madre de Dios, promoviendo el desarrollo sostenible del agro amazónico.</p><p><em>Nota de demostración: el texto completo de esta nota se migrará desde el portal anterior.</em></p>",
                    'is_featured' => $featured,
                    'is_published' => true,
                    'published_at' => "{$date} 10:00:00",
                ],
            );
        }
    }
}
