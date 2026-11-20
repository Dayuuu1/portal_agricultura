<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Fillable(['key', 'label', 'value', 'group', 'type', 'sort_order'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory, LogsActivity;

    public const CACHE_KEY = 'portal.settings';

    /**
     * Images bundled with the site, used while the entity has not uploaded its own.
     */
    public const DEFAULT_IMAGES = [
        'site_logo' => 'images/logo-emblema.png',
        'site_favicon' => 'favicon-32.png',
    ];

    /**
     * Folder of the public disk that holds the uploaded logo and icon.
     */
    public const IMAGE_DIRECTORY = 'portal';

    /**
     * Placeholder mission and vision installed with the portal, to be replaced by the official texts.
     */
    public const REFERENCE_TEXTS = [
        'mission' => 'Promover el desarrollo agrario competitivo, sostenible e inclusivo de Madre de Dios, brindando a los productores servicios de asistencia técnica, información agraria, saneamiento físico legal de la propiedad rural y articulación a mercados.',
        'vision' => 'Madre de Dios con productores agrarios organizados, competitivos y articulados a los mercados, que aprovechan de forma sostenible la biodiversidad amazónica.',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));

        // A replaced logo or icon is no longer referenced anywhere, so its file is removed.
        static::updated(function (Setting $setting): void {
            $previous = $setting->getRawOriginal('value');

            if ($setting->type === 'image' && $setting->wasChanged('value') && is_string($previous)
                && str_starts_with($previous, self::IMAGE_DIRECTORY.'/')) {
                Storage::disk('public')->delete($previous);
            }
        });
    }

    /**
     * Uploaded logo, or the one bundled with the site.
     */
    public static function logoUrl(): string
    {
        return static::imageUrl('site_logo');
    }

    /**
     * Uploaded browser tab icon, or the one bundled with the site.
     */
    public static function faviconUrl(): string
    {
        return static::imageUrl('site_favicon');
    }

    public static function imageUrl(string $key): string
    {
        return once(function () use ($key): string {
            $path = static::valueOf($key);

            return filled($path) && Storage::disk('public')->exists($path)
                ? Storage::disk('public')->url($path)
                : asset(self::DEFAULT_IMAGES[$key]);
        });
    }

    /**
     * Short name shown next to the logo and as the name of the admin panel.
     */
    public static function shortName(): string
    {
        return static::valueOf('site_short_name') ?: 'Dirección Regional Agraria';
    }

    /**
     * @return array<string, string|null>
     */
    public static function allCached(): array
    {
        return once(fn (): array => Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => static::query()->pluck('value', 'key')->all(),
        ));
    }

    public static function valueOf(string $key, ?string $default = null): ?string
    {
        return static::allCached()[$key] ?? $default;
    }
}
