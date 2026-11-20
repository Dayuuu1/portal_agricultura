<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUniqueSlug
{
    public static function bootHasUniqueSlug(): void
    {
        static::saving(function (Model $model): void {
            if (blank($model->slug)) {
                $model->slug = static::generateUniqueSlug((string) $model->{static::slugSource()}, $model->getKey());
            }
        });
    }

    protected static function slugSource(): string
    {
        return 'title';
    }

    public static function generateUniqueSlug(string $value, int|string|null $ignoreId = null): string
    {
        $normalized = str_replace(['/', '°', 'º'], [' ', '', ''], $value);
        $base = rtrim(Str::limit(Str::slug($normalized), 150, ''), '-') ?: 'registro';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
