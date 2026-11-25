<?php

namespace App\Models;

use Database\Factories\AnnouncementStatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

/**
 * What visitors did with an announcement on one day. Times, not people: no IP, cookie or identifier is stored.
 */
#[Fillable(['announcement_id', 'date', 'views', 'clicks', 'dismissals'])]
class AnnouncementStat extends Model
{
    /** @use HasFactory<AnnouncementStatFactory> */
    use HasFactory;

    /**
     * Event reported by the window (resources/js/announcements.js) => counter it adds to.
     */
    public const EVENTS = [
        'view' => 'views',
        'click' => 'clicks',
        'dismiss' => 'dismissals',
    ];

    public $timestamps = false;

    /**
     * Plain dates, so rows written by Eloquent and by the upsert below share the same unique key.
     */
    protected $dateFormat = 'Y-m-d';

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'views' => 'integer',
            'clicks' => 'integer',
            'dismissals' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Announcement, $this>
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    /**
     * Adds one to today's counter in a single statement, so simultaneous visitors never overwrite each other.
     */
    public static function record(Announcement $announcement, string $event): void
    {
        $column = self::EVENTS[$event];

        static::query()->upsert(
            [['announcement_id' => $announcement->getKey(), 'date' => today()->format('Y-m-d'), 'views' => 0, 'clicks' => 0, 'dismissals' => 0, $column => 1]],
            uniqueBy: ['announcement_id', 'date'],
            update: [$column => DB::raw($column.' + 1')],
        );
    }

    /**
     * Sums of the announcement's counters, over its whole life or over the last $days days (today included).
     *
     * @return array{views: int, clicks: int, dismissals: int}
     */
    public static function totals(Announcement $announcement, ?int $days = null): array
    {
        $row = static::query()
            ->whereBelongsTo($announcement)
            ->when($days, fn (Builder $query) => $query->where('date', '>=', today()->subDays($days - 1)->format('Y-m-d')))
            ->toBase()
            ->selectRaw('coalesce(sum(views), 0) as views, coalesce(sum(clicks), 0) as clicks, coalesce(sum(dismissals), 0) as dismissals')
            ->first();

        return ['views' => (int) $row->views, 'clicks' => (int) $row->clicks, 'dismissals' => (int) $row->dismissals];
    }

    /**
     * Day by day over the last $days days (today included), oldest first; days without activity count zero,
     * so charts show the quiet days too.
     *
     * @return array<string, array{views: int, clicks: int, dismissals: int}> Keyed by "Y-m-d".
     */
    public static function series(Announcement $announcement, int $days): array
    {
        $start = today()->subDays($days - 1);
        $rows = static::query()
            ->whereBelongsTo($announcement)
            ->where('date', '>=', $start->format('Y-m-d'))
            ->get()
            ->keyBy(fn (self $stat): string => $stat->date->format('Y-m-d'));

        $series = [];

        for ($day = $start->copy(); $day->lessThanOrEqualTo(today()); $day->addDay()) {
            $row = $rows->get($day->format('Y-m-d'));
            $series[$day->format('Y-m-d')] = [
                'views' => $row->views ?? 0,
                'clicks' => $row->clicks ?? 0,
                'dismissals' => $row->dismissals ?? 0,
            ];
        }

        return $series;
    }

    /**
     * Share of the views, in percent, or null when there were no views to compare with.
     */
    public static function rate(int $count, int $views): ?float
    {
        return $views > 0 ? round($count / $views * 100, 1) : null;
    }

    /**
     * In the panel's locale, like the numeric table columns ("1.500").
     */
    public static function formatCount(int $count): string
    {
        return (string) Number::format($count, locale: config('app.locale'));
    }

    /**
     * "4,2 %", or a dash when there is nothing to compare with.
     */
    public static function formatRate(?float $rate): string
    {
        return $rate === null ? '—' : Number::format($rate, precision: 1, locale: config('app.locale')).' %';
    }
}
