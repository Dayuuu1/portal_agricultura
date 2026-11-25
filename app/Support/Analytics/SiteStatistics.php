<?php

namespace App\Support\Analytics;

use App\Enums\DeviceType;
use App\Enums\SiteEventType;
use App\Enums\SiteSection;
use App\Enums\TrafficSource;
use App\Models\Document;
use App\Models\PageView;
use App\Models\SiteEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

/**
 * Reports of «Estadísticas del portal». Every query groups by plain columns (date, hour, path, section…), so it
 * runs the same on SQLite and MySQL.
 */
final class SiteStatistics
{
    /**
     * @return Builder<PageView>
     */
    private static function views(AnalyticsPeriod $period): Builder
    {
        return PageView::query()->whereBetween('date', [$period->from(), $period->to()]);
    }

    /**
     * @return Builder<SiteEvent>
     */
    private static function events(AnalyticsPeriod $period, SiteEventType $type): Builder
    {
        return SiteEvent::query()->where('type', $type)->whereBetween('date', [$period->from(), $period->to()]);
    }

    /**
     * Visitors are told apart within each day only (see PageViewRecorder), so over several days this is the sum
     * of each day's visitors.
     *
     * @return array{visitors: int, views: int, visits: int, bounceRate: ?float, visitDuration: ?int, pagesPerVisit: ?float}
     */
    public static function summary(AnalyticsPeriod $period): array
    {
        $visits = DB::query()
            ->fromSub(self::views($period)->toBase()->select('session')->selectRaw('count(*) as pages, coalesce(sum(duration), 0) as seconds')->groupBy('session'), 'visits')
            ->selectRaw('count(*) as total, coalesce(sum(case when pages = 1 then 1 else 0 end), 0) as bounces, avg(seconds) as seconds')
            ->first();

        $views = self::views($period)->count();
        $total = (int) $visits->total;

        return [
            'visitors' => (int) self::views($period)->toBase()->selectRaw('count(distinct visitor) as total')->value('total'),
            'views' => $views,
            'visits' => $total,
            'bounceRate' => $total > 0 ? round($visits->bounces / $total * 100, 1) : null,
            'visitDuration' => $total > 0 ? (int) round((float) $visits->seconds) : null,
            'pagesPerVisit' => $total > 0 ? round($views / $total, 1) : null,
        ];
    }

    /**
     * Visitors and page views per day, or per hour when the period is a single day.
     *
     * @return array<string, array{visitors: int, views: int}> Keyed by "Y-m-d" or by hour ("0" to "23").
     */
    public static function trend(AnalyticsPeriod $period): array
    {
        $column = $period->isSingleDay() ? 'hour' : 'date';
        $keys = $period->isSingleDay() ? array_map('strval', range(0, 23)) : $period->dates();

        $rows = self::views($period)->toBase()
            ->select($column)
            ->selectRaw('count(*) as views, count(distinct visitor) as visitors')
            ->groupBy($column)
            ->get()
            ->keyBy(fn (object $row): string => (string) $row->{$column});

        return collect($keys)->mapWithKeys(fn (string $key): array => [$key => [
            'visitors' => (int) ($rows[$key]->visitors ?? 0),
            'views' => (int) ($rows[$key]->views ?? 0),
        ]])->all();
    }

    /**
     * @return list<array{path: string, title: ?string, views: int, visitors: int, duration: ?int}>
     */
    public static function topPages(AnalyticsPeriod $period, int $limit = 10, ?SiteSection $section = null, bool $detailPagesOnly = false): array
    {
        return self::views($period)->toBase()
            ->when($section, fn ($query) => $query->where('section', $section->value))
            // Pages of one item ("/documentos/resolucion-123"), not the listings of the section.
            ->when($detailPagesOnly, fn ($query) => $query->where('path', 'like', '/%/%'))
            ->select('path')
            ->selectRaw('max(title) as title, count(*) as views, count(distinct visitor) as visitors, avg(duration) as duration')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'path' => $row->path,
                'title' => $row->title,
                'views' => (int) $row->views,
                'visitors' => (int) $row->visitors,
                'duration' => $row->duration === null ? null : (int) round((float) $row->duration),
            ])
            ->all();
    }

    /**
     * @return list<array{section: SiteSection, views: int, visitors: int}>
     */
    public static function sections(AnalyticsPeriod $period): array
    {
        return self::views($period)->toBase()
            ->select('section')
            ->selectRaw('count(*) as views, count(distinct visitor) as visitors')
            ->groupBy('section')
            ->orderByDesc('views')
            ->get()
            ->map(fn (object $row): array => [
                'section' => SiteSection::tryFrom($row->section) ?? SiteSection::Other,
                'views' => (int) $row->views,
                'visitors' => (int) $row->visitors,
            ])
            ->all();
    }

    /**
     * Visits by where they came from, each with its most frequent referring site.
     *
     * @return list<array{source: TrafficSource, visits: int, topSite: ?string}>
     */
    public static function sources(AnalyticsPeriod $period): array
    {
        $sites = self::views($period)->toBase()
            ->where('is_entry', true)
            ->whereNotNull('referrer_host')
            ->select('source', 'referrer_host')
            ->selectRaw('count(*) as visits')
            ->groupBy('source', 'referrer_host')
            ->orderByDesc('visits')
            ->get()
            ->groupBy('source')
            ->map(fn ($rows) => $rows->first()->referrer_host);

        return self::views($period)->toBase()
            ->where('is_entry', true)
            ->select('source')
            ->selectRaw('count(*) as visits')
            ->groupBy('source')
            ->orderByDesc('visits')
            ->get()
            ->map(fn (object $row): array => [
                'source' => TrafficSource::tryFrom((string) $row->source) ?? TrafficSource::Direct,
                'visits' => (int) $row->visits,
                'topSite' => $sites[$row->source] ?? null,
            ])
            ->all();
    }

    /**
     * Sites whose links started visits (google.com, facebook.com, gob.pe…).
     *
     * @return array<string, int> Site => visits, most first.
     */
    public static function referrers(AnalyticsPeriod $period, int $limit = 50): array
    {
        return self::views($period)->toBase()
            ->where('is_entry', true)
            ->whereNotNull('referrer_host')
            ->select('referrer_host')
            ->selectRaw('count(*) as visits')
            ->groupBy('referrer_host')
            ->orderByDesc('visits')
            ->limit($limit)
            ->pluck('visits', 'referrer_host')
            ->map(fn (int|string $visits): int => (int) $visits)
            ->all();
    }

    /**
     * Visitors by device, browser or operating system.
     *
     * @param  'device'|'browser'|'os'  $column
     * @return array<string, int> Label => visitors, most used first.
     */
    public static function technology(AnalyticsPeriod $period, string $column): array
    {
        return self::views($period)->toBase()
            ->select($column)
            ->selectRaw('count(distinct visitor) as visitors')
            ->groupBy($column)
            ->orderByDesc('visitors')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                ($column === 'device' ? (DeviceType::tryFrom($row->device)?->getLabel() ?? $row->device) : $row->{$column}) => (int) $row->visitors,
            ])
            ->all();
    }

    /**
     * @return array<int, int> Page views per hour of the day, 0 to 23.
     */
    public static function hours(AnalyticsPeriod $period): array
    {
        $rows = self::views($period)->toBase()
            ->select('hour')
            ->selectRaw('count(*) as views')
            ->groupBy('hour')
            ->pluck('views', 'hour');

        return collect(range(0, 23))->mapWithKeys(fn (int $hour): array => [$hour => (int) ($rows[$hour] ?? 0)])->all();
    }

    /**
     * @return list<array{term: string, searches: int, results: int, scopes: string}>
     */
    public static function searches(AnalyticsPeriod $period, bool $withoutResults = false, int $limit = 10): array
    {
        return self::events($period, SiteEventType::Search)->toBase()
            ->when($withoutResults, fn ($query) => $query->where('value', 0))
            ->select('label')
            ->selectRaw('count(*) as searches, max(value) as results, group_concat(distinct context) as scopes')
            ->groupBy('label')
            ->orderByDesc('searches')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'term' => $row->label,
                'searches' => (int) $row->searches,
                'results' => (int) $row->results,
                'scopes' => (string) $row->scopes,
            ])
            ->all();
    }

    /**
     * @return array{searches: int, withoutResults: int}
     */
    public static function searchTotals(AnalyticsPeriod $period): array
    {
        $row = self::events($period, SiteEventType::Search)->toBase()
            ->selectRaw('count(*) as searches, coalesce(sum(case when value = 0 then 1 else 0 end), 0) as empty')
            ->first();

        return ['searches' => (int) $row->searches, 'withoutResults' => (int) $row->empty];
    }

    /**
     * Documents whose «Descargar» was clicked, with their title and type.
     *
     * @return list<array{title: string, type: ?string, slug: string, downloads: int}>
     */
    public static function downloads(AnalyticsPeriod $period, int $limit = 10): array
    {
        $counts = self::events($period, SiteEventType::Download)->toBase()
            ->select('label')
            ->selectRaw('count(*) as downloads')
            ->groupBy('label')
            ->orderByDesc('downloads')
            ->limit($limit)
            ->pluck('downloads', 'label');

        $documents = Document::query()->with('documentType')->whereIn('slug', $counts->keys())->get()->keyBy('slug');

        return $counts->map(fn (int|string $downloads, string $slug): array => [
            'title' => $documents->get($slug)->title ?? $slug,
            'type' => $documents->get($slug)?->documentType?->name,
            'slug' => $slug,
            'downloads' => (int) $downloads,
        ])->values()->all();
    }

    /**
     * Times each label was recorded for a kind of event (links to other sites, addresses not found).
     *
     * @return array<string, int>
     */
    public static function eventCounts(AnalyticsPeriod $period, SiteEventType $type, int $limit = 10): array
    {
        return self::events($period, $type)->toBase()
            ->select('label')
            ->selectRaw('count(*) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'label')
            ->map(fn (int|string $total): int => (int) $total)
            ->all();
    }

    public static function eventTotal(AnalyticsPeriod $period, SiteEventType $type): int
    {
        return self::events($period, $type)->count();
    }

    /**
     * Visitors who opened a page in the last minutes, and the sections they are in.
     *
     * @return array{visitors: int, sections: array<string, int>}
     */
    public static function live(int $minutes = 5): array
    {
        $recent = PageView::query()->where('created_at', '>=', now()->subMinutes($minutes));

        return [
            'visitors' => (int) (clone $recent)->distinct()->count('visitor'),
            'sections' => (clone $recent)->toBase()
                ->select('section')
                ->selectRaw('count(distinct visitor) as visitors')
                ->groupBy('section')
                ->orderByDesc('visitors')
                ->pluck('visitors', 'section')
                ->mapWithKeys(fn (int|string $visitors, string $section): array => [(SiteSection::tryFrom($section) ?? SiteSection::Other)->getLabel() => (int) $visitors])
                ->all(),
        ];
    }

    /**
     * Percentage change against the previous period, or null when there is nothing to compare with.
     */
    public static function change(int|float|null $current, int|float|null $previous): ?float
    {
        if ($current === null || $previous === null || (float) $previous === 0.0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    public static function formatNumber(int|float $value, int $precision = 0): string
    {
        return (string) Number::format($value, precision: $precision, locale: config('app.locale'));
    }

    /**
     * "2 min 35 s", "45 s" or "1 h 05 min".
     */
    public static function formatDuration(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        return match (true) {
            $seconds < 60 => $seconds.' s',
            $seconds < 3600 => intdiv($seconds, 60).' min '.str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT).' s',
            default => intdiv($seconds, 3600).' h '.str_pad((string) intdiv($seconds % 3600, 60), 2, '0', STR_PAD_LEFT).' min',
        };
    }
}
