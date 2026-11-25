<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Ownership codes of Google Search Console and Bing Webmaster Tools, edited in «Datos del portal › Buscadores».
 * Both start empty: no tag is printed until the entity pastes its own code.
 */
return new class extends Migration
{
    private const KEYS = ['google_site_verification', 'bing_site_verification'];

    public function up(): void
    {
        $settings = [
            ['google_site_verification', 'Código de verificación de Google Search Console'],
            ['bing_site_verification', 'Código de verificación de Bing Webmaster Tools'],
        ];

        foreach ($settings as $order => [$key, $label]) {
            Setting::query()->firstOrCreate(['key' => $key], [
                'label' => $label,
                'value' => null,
                'group' => 'buscadores',
                'type' => 'text',
                'sort_order' => 90 + $order,
            ]);
        }
    }

    public function down(): void
    {
        Setting::query()->whereIn('key', self::KEYS)->delete();
    }
};
