<?php

namespace App\Support\Documents;

use App\Models\Document;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Every publication of one indicator within a year, e.g. all the "Precio en chacra de los principales cultivos"
 * reports of 2026, laid out on a January–December calendar when the titles name their month.
 */
final readonly class DocumentSeries
{
    /**
     * @param  Collection<int, Document>  $calendar  Keyed by month number (1–12); empty when the titles carry no month.
     * @param  Collection<int, array{document: Document, label: string, isPublicationDate: bool}>  $others  Publications outside the calendar, oldest first.
     */
    public function __construct(
        public string $key,
        public string $name,
        public int $count,
        public Collection $calendar,
        public Collection $others,
    ) {}

    /**
     * Series in the order the office publishes them (first upload first).
     *
     * @param  Collection<int, Document>  $documents
     * @return Collection<int, self>
     */
    public static function groupFrom(Collection $documents, int $year): Collection
    {
        return $documents->sortBy('id')
            ->map(fn (Document $document): array => [
                'document' => $document,
                'period' => TitlePeriod::fromTitle($document->title, $document->period_year),
            ])
            ->groupBy(fn (array $item): string => $item['period']->seriesKey())
            ->map(fn (Collection $items, string $key): self => self::make($key, $items, $year))
            ->values();
    }

    /**
     * Short month names keyed by month number, for the calendar header ("ene" … "dic").
     *
     * @return array<int, string>
     */
    public static function monthLabels(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [$month => rtrim(Carbon::create(2000, $month)->translatedFormat('M'), '.')])
            ->all();
    }

    public function hasCalendar(): bool
    {
        return $this->calendar->isNotEmpty();
    }

    /**
     * @param  Collection<int, array{document: Document, period: TitlePeriod}>  $items
     */
    private static function make(string $key, Collection $items, int $year): self
    {
        $chronological = $items->sortBy(fn (array $item): string => self::sortKey($item))->values();

        $calendar = collect();
        foreach ($chronological as $item) {
            $period = $item['period'];

            if ($period->hasMonth() && $period->day === null && $period->year === $year && ! $calendar->has($period->month)) {
                $calendar->put($period->month, $item['document']);
            }
        }

        // A calendar with a single month says nothing a list would not.
        if ($calendar->count() < 2) {
            $calendar = collect();
        }

        $labelUses = [];
        $others = $chronological
            ->reject(fn (array $item): bool => $calendar->contains(fn (Document $document): bool => $document->is($item['document'])))
            ->map(function (array $item) use ($year, &$labelUses): array {
                $label = self::chipLabel($item, $year);
                $labelUses[$label] = ($labelUses[$label] ?? 0) + 1;

                return [
                    'document' => $item['document'],
                    'label' => $labelUses[$label] > 1 ? "{$label} ({$labelUses[$label]})" : $label,
                    'isPublicationDate' => ! $item['period']->hasMonth(),
                ];
            })
            ->values();

        return new self(
            key: Str::slug($key) ?: 'serie',
            name: $items->last()['period']->seriesName,
            count: $items->count(),
            calendar: $calendar->sortKeys(),
            others: $others,
        );
    }

    /**
     * @param  array{document: Document, period: TitlePeriod}  $item
     */
    private static function sortKey(array $item): string
    {
        $period = $item['period'];

        if ($period->hasMonth()) {
            return sprintf('%04d-%02d-%02d', $period->year, $period->month, $period->day ?? 1);
        }

        return $item['document']->issued_at?->toDateString() ?? '9999-12-31';
    }

    /**
     * "jul", "2 abr", "27 feb" or "jul 2026" when the date falls outside the year being browsed.
     *
     * @param  array{document: Document, period: TitlePeriod}  $item
     */
    private static function chipLabel(array $item, int $year): string
    {
        $period = $item['period'];
        $date = $period->hasMonth()
            ? Carbon::create($period->year, $period->month, $period->day ?? 1)
            : $item['document']->issued_at;

        if ($date === null) {
            return 'Sin fecha';
        }

        $showsDay = ! $period->hasMonth() || $period->day !== null;
        $label = ($showsDay ? $date->day.' ' : '').rtrim($date->translatedFormat('M'), '.');

        return $date->year === $year ? $label : "{$label} {$date->year}";
    }
}
