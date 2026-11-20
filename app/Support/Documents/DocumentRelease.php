<?php

namespace App\Support\Documents;

use App\Models\Document;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Periodic documents shown together: the reports for one month ("Julio 2025") or, when the titles
 * carry no month (older uploads such as "… año 2019"), everything published on the same day.
 */
final readonly class DocumentRelease
{
    /**
     * @param  Collection<int, array{document: Document, label: string}>  $entries
     */
    public function __construct(
        public string $key,
        public string $heading,
        public string $badgeTop,
        public string $badgeBottom,
        public bool $isReportingPeriod,
        public ?CarbonInterface $publishedOn,
        public Collection $entries,
    ) {}

    /**
     * Monthly releases come first (newest month first), followed by the undated publication days.
     *
     * @param  Collection<int, Document>  $documents
     * @return Collection<int, self>
     */
    public static function groupFrom(Collection $documents): Collection
    {
        $items = $documents->sortBy('id')->map(fn (Document $document): array => [
            'document' => $document,
            'period' => TitlePeriod::fromTitle($document->title, $document->period_year),
        ]);

        [$withMonth, $withoutMonth] = $items->partition(fn (array $item): bool => $item['period']->hasMonth());

        $monthly = $withMonth
            ->groupBy(fn (array $item): string => sprintf('%04d-%02d', $item['period']->year, $item['period']->month))
            ->sortKeysDesc();

        // A document without a month joins the monthly release uploaded the same day (e.g. the sowing campaign report).
        $monthByPublicationDay = [];
        foreach ($monthly as $month => $group) {
            foreach ($group as $item) {
                if ($day = $item['document']->issued_at?->toDateString()) {
                    $monthByPublicationDay[$day] ??= $month;
                }
            }
        }

        $daily = collect();
        foreach ($withoutMonth as $item) {
            $day = $item['document']->issued_at?->toDateString();

            if ($day !== null && isset($monthByPublicationDay[$day])) {
                $monthly[$monthByPublicationDay[$day]]->push($item);
            } else {
                $daily->put($day ?? '', $daily->get($day ?? '', collect())->push($item));
            }
        }

        return $monthly->map(fn (Collection $group, string $month): self => self::forMonth($month, $group))
            ->values()
            ->concat($daily->sortKeysDesc()->map(fn (Collection $group, string $day): self => self::forDay($day, $group))->values());
    }

    /**
     * @param  Collection<int, array{document: Document, period: TitlePeriod}>  $items
     */
    private static function forMonth(string $month, Collection $items): self
    {
        $date = Carbon::createFromFormat('!Y-m', $month);

        return new self(
            key: 'mes-'.$month,
            heading: Str::ucfirst($date->translatedFormat('F Y')),
            badgeTop: rtrim($date->translatedFormat('M'), '.'),
            badgeBottom: (string) $date->year,
            isReportingPeriod: true,
            publishedOn: $items->map(fn (array $item): ?CarbonInterface => $item['document']->issued_at)->filter()->min(),
            entries: $items->map(fn (array $item): array => [
                'document' => $item['document'],
                'label' => $item['period']->hasMonth() && $item['period']->day === null ? $item['period']->seriesName : $item['document']->title,
            ])->values(),
        );
    }

    /**
     * @param  Collection<int, array{document: Document, period: TitlePeriod}>  $items
     */
    private static function forDay(string $day, Collection $items): self
    {
        $date = $day !== '' ? Carbon::parse($day) : null;

        return new self(
            key: 'dia-'.($day ?: 'sin-fecha'),
            heading: $date ? 'Publicado el '.$date->translatedFormat('j \d\e F \d\e Y') : 'Sin fecha de publicación',
            badgeTop: $date ? (string) $date->day : '—',
            badgeBottom: $date ? rtrim($date->translatedFormat('M'), '.').' '.$date->year : '',
            isReportingPeriod: false,
            publishedOn: $date,
            entries: $items->map(fn (array $item): array => ['document' => $item['document'], 'label' => $item['document']->title])->values(),
        );
    }
}
