<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['general', 'site_name', 'Nombre de la entidad', 'text', 'Dirección Regional de Desarrollo Agropecuario y Riego de Madre de Dios'],
            ['general', 'site_short_name', 'Nombre corto (junto al logo y en el panel)', 'text', 'Dirección Regional Agraria'],
            ['general', 'site_acronym', 'Siglas', 'text', 'DRDAR'],
            ['general', 'parent_entity', 'Entidad a la que pertenece', 'text', 'Gobierno Regional de Madre de Dios'],
            ['general', 'site_logo', 'Logo', 'image', null],
            ['general', 'site_favicon', 'Ícono de la pestaña del navegador', 'image', null],
            ['general', 'site_description', 'Descripción breve', 'textarea', 'Órgano del Gobierno Regional de Madre de Dios encargado de promover, supervisar, evaluar, dirigir y controlar las actividades productivas agrarias de la región.'],
            ['institucional', 'mission', 'Misión (texto referencial: reemplazar por el oficial)', 'textarea', Setting::REFERENCE_TEXTS['mission']],
            ['institucional', 'vision', 'Visión (texto referencial: reemplazar por el oficial)', 'textarea', Setting::REFERENCE_TEXTS['vision']],
            ['contacto', 'address', 'Dirección', 'text', 'Av. Fitzcarrald N° 265, Puerto Maldonado, Tambopata – Madre de Dios'],
            ['contacto', 'phone_main', 'Teléfono central', 'text', '082 572668'],
            ['contacto', 'phone_director', 'Teléfono Dirección', 'text', '082 639069'],
            ['contacto', 'phone_procurement', 'Teléfono Abastecimiento', 'text', '082 639067'],
            ['contacto', 'email', 'Correo institucional', 'email', 'info@dramdd.gob.pe'],
            ['contacto', 'schedule', 'Horario de atención', 'text', 'Lunes a viernes de 8:00 a.m. a 4:00 p.m.'],
            ['contacto', 'map_embed_url', 'URL del mapa (iframe de OpenStreetMap o Google Maps)', 'url', null],
            ['redes', 'facebook_url', 'Facebook', 'url', 'https://www.facebook.com/agriculturamadrededios'],
            ['redes', 'youtube_url', 'YouTube', 'url', 'https://www.youtube.com/channel/UC5trdgJoBbuagjZn8AdE8LQ'],
            ['servicios', 'mesa_partes_url', 'Mesa de partes virtual', 'url', null],
            ['servicios', 'libro_reclamaciones_url', 'Libro de reclamaciones en GOB.PE (opcional: enlace a la plataforma digital del Estado)', 'url', null],
            ['servicios', 'complaints_email', 'Correo que recibe el Libro de Reclamaciones', 'email', 'info@dramdd.gob.pe'],
            ['servicios', 'transparencia_url', 'Portal de Transparencia Estándar (PTE)', 'url', null],
            ['servicios', 'sigaweb_url', 'SIGAWEB (sistema interno)', 'url', 'http://200.37.143.226:8000/SIGAWEB/'],
        ];

        foreach ($settings as $order => [$group, $key, $label, $type, $value]) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['label' => $label, 'value' => $value, 'group' => $group, 'type' => $type, 'sort_order' => $order],
            );
        }
    }
}
