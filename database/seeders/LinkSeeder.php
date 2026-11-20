<?php

namespace Database\Seeders;

use App\Enums\LinkGroup;
use App\Models\Link;
use Illuminate\Database\Seeder;

class LinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            [LinkGroup::EnlacesInteres, 'SENASA', 'https://www.senasa.gob.pe/senasa/', 'Servicio Nacional de Sanidad Agraria'],
            [LinkGroup::EnlacesInteres, 'AgroRural', 'http://www.agrorural.gob.pe/', 'Programa de Desarrollo Productivo Agrario Rural'],
            [LinkGroup::EnlacesInteres, 'Agroideas', 'https://www.agroideas.gob.pe/', 'Programa de Compensaciones para la Competitividad'],
            [LinkGroup::EnlacesInteres, 'Agrobanco', 'https://www.agrobanco.com.pe/', 'Banco Agropecuario'],
            [LinkGroup::EnlacesInteres, 'INIA', 'http://www.inia.gob.pe/', 'Instituto Nacional de Innovación Agraria'],
            [LinkGroup::EnlacesInteres, 'ANA', 'http://www.ana.gob.pe/', 'Autoridad Nacional del Agua'],
            [LinkGroup::EnlacesInteres, 'RNSDD', 'http://www.sanciones.gob.pe:8081/transparencia/', 'Registro Nacional de Sanciones contra Servidores Civiles'],
            [LinkGroup::PortalesEstado, 'MIDAGRI', 'https://www.gob.pe/midagri', 'Ministerio de Desarrollo Agrario y Riego'],
            [LinkGroup::PortalesEstado, 'gob.pe', 'https://www.gob.pe/', 'Plataforma digital única del Estado peruano'],
            [LinkGroup::PortalesEstado, 'GOREMAD', 'http://regionmadrededios.gob.pe/new/', 'Gobierno Regional de Madre de Dios'],
            [LinkGroup::Servicios, 'SIGAWEB', 'http://200.37.143.226:8000/SIGAWEB/', 'Sistema Integrado de Gestión Administrativa (uso interno)'],
        ];

        foreach ($links as $order => [$group, $name, $url, $description]) {
            Link::query()->updateOrCreate(
                ['name' => $name],
                ['group' => $group, 'url' => $url, 'description' => $description, 'sort_order' => $order, 'is_active' => true, 'show_in_carousel' => $group === LinkGroup::EnlacesInteres],
            );
        }
    }
}
