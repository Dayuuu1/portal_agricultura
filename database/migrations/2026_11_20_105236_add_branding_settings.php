<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Makes the entity's short name, logo and browser icon editable from «Datos del portal». Existing rows are
 * not touched: the short name starts with the text the logo already showed, and the images start empty,
 * which keeps the bundled logo and icon until the entity uploads its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['site_short_name', 'Nombre corto (junto al logo y en el panel)', 'text', 'Dirección Regional Agraria', 0],
            ['site_logo', 'Logo', 'image', null, 3],
            ['site_favicon', 'Ícono de la pestaña del navegador', 'image', null, 3],
        ];

        foreach ($settings as [$key, $label, $type, $value, $sortOrder]) {
            Setting::query()->firstOrCreate(['key' => $key], [
                'label' => $label,
                'value' => $value,
                'group' => 'general',
                'type' => $type,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    public function down(): void
    {
        Setting::query()->whereIn('key', ['site_short_name', 'site_logo', 'site_favicon'])->delete();
    }
};
