<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Non-working days decreed for the public sector (beyond the national holidays already known), used for terms.
 */
#[Fillable(['date', 'description'])]
class NonWorkingDay extends Model
{
    use LogsActivity;

    public const CACHE_KEY = 'mesa-partes.non-working-days';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return list<string> Dates as Y-m-d.
     */
    public static function dates(): array
    {
        // Term calculations ask once per day counted, so the list is also kept for the rest of the request.
        return once(fn (): array => Cache::rememberForever(self::CACHE_KEY, fn (): array => static::query()->orderBy('date')->pluck('date')
            ->map(fn ($date): string => $date->format('Y-m-d'))->all()));
    }

    public function activityLogLabel(): string
    {
        return $this->date->format('d/m/Y').' · '.$this->description;
    }
}
