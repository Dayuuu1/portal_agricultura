<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            ['nosotros', 'Nosotros', 'Quiénes somos y qué hacemos por el agro de Madre de Dios.',
                '<p>La Dirección Regional de Desarrollo Agropecuario y Riego de Madre de Dios es un órgano del Gobierno Regional de Madre de Dios encargado de promover, supervisar, evaluar, dirigir y controlar las actividades productivas agrarias de la región, en concordancia con la política nacional agraria.</p><p>Brinda servicios de asistencia técnica, información y estadística agraria, saneamiento físico legal de la propiedad rural, promoción de la competitividad y articulación de los productores a los mercados, a través de su sede central y sus agencias agrarias.</p>'],
            ['codigo-de-etica', 'Código de Ética de la Función Pública', 'Principios, deberes y prohibiciones éticas de los servidores públicos.',
                '<p>Los servidores de la entidad se rigen por la Ley N° 27815, Ley del Código de Ética de la Función Pública, y su reglamento.</p><h2>Principios</h2><ul><li>Respeto</li><li>Probidad</li><li>Eficiencia</li><li>Idoneidad</li><li>Veracidad</li><li>Lealtad y obediencia</li><li>Justicia y equidad</li><li>Lealtad al Estado de Derecho</li></ul><p>Para reportar presuntos actos contrarios a la ética, utilice los canales oficiales de denuncia de la entidad.</p>'],
            ['politica-de-privacidad', 'Política de privacidad', 'Tratamiento de los datos personales recibidos a través del portal.',
                '<p>Los datos personales que usted registre en los formularios de este portal se tratarán conforme a la Ley N° 29733, Ley de Protección de Datos Personales, y su reglamento. Se usarán solo para atender su solicitud y no se cederán a terceros sin su consentimiento, salvo mandato legal.</p>'],
        ];

        foreach ($pages as [$slug, $title, $excerpt, $body]) {
            Page::query()->updateOrCreate(
                ['slug' => $slug],
                ['title' => $title, 'excerpt' => $excerpt, 'body' => $body, 'is_published' => true],
            );
        }
    }
}
