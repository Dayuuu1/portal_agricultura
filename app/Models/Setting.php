<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'label', 'value', 'group', 'type', 'sort_order'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory, LogsActivity;

    public const CACHE_KEY = 'portal.settings';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
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
