<?php

namespace App\Models;

use App\Enums\DeviceType;
use App\Enums\SiteSection;
use App\Enums\TrafficSource;
use Database\Factories\PageViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A page a visitor opened on the public site (App\Support\Analytics\PageViewRecorder).
 */
#[Fillable([
    'visitor', 'session', 'is_entry', 'path', 'title', 'section', 'source', 'referrer_host', 'utm_source',
    'utm_campaign', 'device', 'browser', 'os', 'duration', 'date', 'hour', 'created_at',
])]
class PageView extends Model
{
    /** @use HasFactory<PageViewFactory> */
    use HasFactory, MassPrunable;

    /**
     * Thirteen months: enough to compare a month with the same month of the year before.
     */
    public const RETENTION_MONTHS = 13;

    /**
     * Longest time on one page that is counted, so a tab forgotten in the foreground does not skew the averages.
     */
    public const MAX_DURATION = 1800;

    public const UPDATED_AT = null;

    /**
     * "date" (Y-m-d, local time) and "hour" are written apart from created_at so reports group by plain columns,
     * the same on SQLite and MySQL. "date" has no cast on purpose: a date cast would store "Y-m-d 00:00:00".
     */
    protected function casts(): array
    {
        return [
            'is_entry' => 'boolean',
            'section' => SiteSection::class,
            'source' => TrafficSource::class,
            'device' => DeviceType::class,
            'duration' => 'integer',
            'hour' => 'integer',
        ];
    }

    /**
     * @return HasMany<SiteEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(SiteEvent::class);
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subMonths(self::RETENTION_MONTHS));
    }
}
