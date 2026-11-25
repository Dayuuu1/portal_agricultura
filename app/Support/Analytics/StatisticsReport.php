<?php

namespace App\Support\Analytics;

use App\Enums\SiteEventType;
use App\Enums\SiteSection;
use Carbon\CarbonImmutable;

/**
 * Everything «Estadísticas del portal» reports for one period, in the shape shared by the panel widgets, the Excel
 * workbook (StatisticsSpreadsheet) and the printable report, so the three always show the same figures.
 */
final class StatisticsReport
{
    /**
     * Rows of each list in the printable report; the Excel workbook takes up to EXPORT_LIMIT.
     */
    public const PRINT_LIMIT = 15;

    public const EXPORT_LIMIT = 500;

    private const SCOPES = ['portal' => 'Buscador general', 'documentos' => 'Documentos', 'noticias' => 'Noticias'];

    /**
     * @var array<string, mixed>
     */
    private array $memo = [];

    public function __construct(public readonly AnalyticsPeriod $period) {}

    /**
     * @return array{visitors: int, views: int, visits: int, bounceRate: ?float, visitDuration: ?int, pagesPerVisit: ?float}
     */
    public function summary(): array
    {
        return $this->memo['summary'] ??= SiteStatistics::summary($this->period);
    }

    /**
     * @return array{visitors: int, views: int, visits: int, bounceRate: ?float, visitDuration: ?int, pagesPerVisit: ?float}
     */
    public function previousSummary(): array
    {
        return $this->memo['previous'] ??= SiteStatistics::summary($this->period->previous());
    }

    /**
     * The key indicators with their value in both periods and whether the change is good news.
     *
     * @return list<array{key: string, label: string, value: string, previousValue: string, current: int|float|null, previous: int|float|null, change: ?float, improved: ?bool}>
     */
    public function indicators(): array
    {
        $current = $this->summary();
        $previous = $this->previousSummary();

        $definitions = [
            'visitors' => ['Visitantes', fn (int|float $value): string => SiteStatistics::formatNumber($value), false],
            'visits' => ['Visitas', fn (int|float $value): string => SiteStatistics::formatNumber($value), false],
            'views' => ['Páginas vistas', fn (int|float $value): string => SiteStatistics::formatNumber($value), false],
            'visitDuration' => ['Duración media de la visita', fn (int|float $value): string => SiteStatistics::formatDuration((int) $value), false],
            'pagesPerVisit' => ['Páginas por visita', fn (int|float $value): string => SiteStatistics::formatNumber($value, 1), false],
            'bounceRate' => ['Tasa de rebote', fn (int|float $value): string => SiteStatistics::formatNumber($value, 1).' %', true],
        ];

        $indicators = [];

        foreach ($definitions as $key => [$label, $format, $lowerIsBetter]) {
            $change = SiteStatistics::change($current[$key], $previous[$key]);

            $indicators[] = [
                'key' => $key,
                'label' => $label,
                'value' => $current[$key] === null ? '—' : $format($current[$key]),
                'previousValue' => $previous[$key] === null ? '—' : $format($previous[$key]),
                'current' => $current[$key],
                'previous' => $previous[$key],
                'change' => $change,
                'improved' => $change === null || $change === 0.0 ? null : ($lowerIsBetter ? $change < 0 : $change > 0),
            ];
        }

        return $indicators;
    }

    /**
     * @return array<string, array{visitors: int, views: int}>
     */
    public function trend(): array
    {
        return $this->memo['trend'] ??= SiteStatistics::trend($this->period);
    }

    /**
     * The trend in bars that fit a printed page: by hour, day, week or month depending on the length of the period.
     *
     * @return array{unit: string, buckets: list<array{label: string, visitors: int, views: int}>}
     */
    public function trendBuckets(): array
    {
        $trend = $this->trend();

        if ($this->period->isSingleDay()) {
            return ['unit' => 'hora', 'buckets' => array_map(
                fn (string $hour, array $values): array => ['label' => str_pad($hour, 2, '0', STR_PAD_LEFT).'h', ...$values],
                array_keys($trend),
                $trend,
            )];
        }

        [$unit, $key, $label] = match (true) {
            $this->period->days() <= 45 => ['día', 'Y-m-d', fn (CarbonImmutable $day): string => $day->format('d/m')],
            $this->period->days() <= 182 => ['semana', 'o-W', fn (CarbonImmutable $day): string => $day->startOfWeek()->format('d/m')],
            default => ['mes', 'Y-m', fn (CarbonImmutable $day): string => ucfirst($day->translatedFormat('M y'))],
        };

        $buckets = [];

        foreach ($trend as $date => $values) {
            $day = CarbonImmutable::parse($date);
            $bucket = $day->format($key);
            $buckets[$bucket] ??= ['label' => $label($day), 'visitors' => 0, 'views' => 0];
            $buckets[$bucket]['visitors'] += $values['visitors'];
            $buckets[$bucket]['views'] += $values['views'];
        }

        return ['unit' => $unit, 'buckets' => array_values($buckets)];
    }

    /**
     * @return list<array{path: string, title: ?string, views: int, visitors: int, duration: ?int}>
     */
    public function pages(int $limit = self::PRINT_LIMIT): array
    {
        return SiteStatistics::topPages($this->period, $limit);
    }

    /**
     * @return list<array{path: string, title: ?string, views: int, visitors: int, duration: ?int}>
     */
    public function documentViews(int $limit = self::PRINT_LIMIT): array
    {
        return SiteStatistics::topPages($this->period, $limit, SiteSection::Documents, detailPagesOnly: true);
    }

    /**
     * @return list<array{label: string, views: int, visitors: int, share: float}>
     */
    public function sections(): array
    {
        $sections = SiteStatistics::sections($this->period);
        $total = max(1, array_sum(array_column($sections, 'views')));

        return array_map(fn (array $section): array => [
            'label' => $section['section']->getLabel(),
            'views' => $section['views'],
            'visitors' => $section['visitors'],
            'share' => round($section['views'] / $total * 100, 1),
        ], $sections);
    }

    /**
     * @return list<array{label: string, visits: int, share: float, topSite: ?string}>
     */
    public function sources(): array
    {
        $sources = SiteStatistics::sources($this->period);
        $total = max(1, array_sum(array_column($sources, 'visits')));

        return array_map(fn (array $source): array => [
            'label' => $source['source']->getLabel(),
            'visits' => $source['visits'],
            'share' => round($source['visits'] / $total * 100, 1),
            'topSite' => $source['topSite'],
        ], $sources);
    }

    /**
     * @return array<string, int>
     */
    public function referrers(int $limit = self::PRINT_LIMIT): array
    {
        return SiteStatistics::referrers($this->period, $limit);
    }

    /**
     * Visitors by device, browser or system, with their share.
     *
     * @param  'device'|'browser'|'os'  $column
     * @return list<array{label: string, visitors: int, share: float}>
     */
    public function technology(string $column): array
    {
        $rows = SiteStatistics::technology($this->period, $column);
        $total = max(1, array_sum($rows));

        return array_map(
            fn (string $label, int $visitors): array => ['label' => $label, 'visitors' => $visitors, 'share' => round($visitors / $total * 100, 1)],
            array_keys($rows),
            $rows,
        );
    }

    /**
     * @return array<int, int>
     */
    public function hours(): array
    {
        return $this->memo['hours'] ??= SiteStatistics::hours($this->period);
    }

    public function peakHour(): ?int
    {
        $hours = $this->hours();

        return array_sum($hours) === 0 ? null : (int) array_search(max($hours), $hours, true);
    }

    /**
     * @return list<array{term: string, searches: int, results: int, scopes: string}>
     */
    public function searches(bool $withoutResults = false, int $limit = self::PRINT_LIMIT): array
    {
        return array_map(fn (array $search): array => [...$search, 'scopes' => self::scopes($search['scopes'])], SiteStatistics::searches($this->period, $withoutResults, $limit));
    }

    /**
     * @return array{searches: int, withoutResults: int}
     */
    public function searchTotals(): array
    {
        return $this->memo['searchTotals'] ??= SiteStatistics::searchTotals($this->period);
    }

    /**
     * @return list<array{title: string, type: ?string, slug: string, downloads: int}>
     */
    public function downloads(int $limit = self::PRINT_LIMIT): array
    {
        return SiteStatistics::downloads($this->period, $limit);
    }

    /**
     * @return array<string, int>
     */
    public function outboundLinks(int $limit = self::PRINT_LIMIT): array
    {
        return SiteStatistics::eventCounts($this->period, SiteEventType::Outbound, $limit);
    }

    /**
     * @return array<string, int>
     */
    public function notFound(int $limit = self::PRINT_LIMIT): array
    {
        return SiteStatistics::eventCounts($this->period, SiteEventType::NotFound, $limit);
    }

    /**
     * Totals of the events of the period, for the tab badges and the report summary.
     */
    public function eventTotal(SiteEventType $type): int
    {
        return $this->memo['events'][$type->value] ??= SiteStatistics::eventTotal($this->period, $type);
    }

    /**
     * "Buscador general, Documentos" from the stored scopes ("portal,documentos").
     */
    public static function scopes(string $scopes): string
    {
        return collect(explode(',', $scopes))
            ->filter()
            ->map(fn (string $scope): string => self::SCOPES[$scope] ?? $scope)
            ->implode(', ') ?: 'Portal';
    }
}
