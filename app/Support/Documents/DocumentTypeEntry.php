<?php

namespace App\Support\Documents;

use App\Models\Document;
use App\Models\DocumentType;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * What a document type holds, for the cards of «Documentos» and «Información agraria»: how many documents,
 * which years they cover and when the last one was published. With details it also knows the last month
 * reported (a bulletin published in November covers October) and every year with documents.
 */
final readonly class DocumentTypeEntry
{
    /**
     * @param  list<int>  $years  Years with documents, newest first (only with details).
     */
    public function __construct(
        public DocumentType $type,
        public int $total,
        public ?int $firstYear,
        public ?int $lastYear,
        public ?CarbonInterface $lastPublishedOn,
        public ?CarbonInterface $latestPeriod = null,
        public array $years = [],
    ) {}

    /**
     * One entry per type with published documents, in the order of the given types.
     *
     * @param  Collection<int, DocumentType>  $types
     * @return Collection<int, self>
     */
    public static function forTypes(Collection $types, bool $withDetails = false): Collection
    {
        $stats = Document::query()->published()->whereIn('document_type_id', $types->modelKeys())->toBase()
            ->selectRaw('document_type_id, count(*) as total, min(period_year) as first_year, max(period_year) as last_year, max(issued_at) as last_issued_at')
            ->groupBy('document_type_id')
            ->get()
            ->keyBy('document_type_id');

        $years = $withDetails ? self::yearsByType($types) : collect();
        $latestPeriods = $withDetails ? self::latestPeriodsByType($stats) : collect();

        return $types
            ->filter(fn (DocumentType $type): bool => $stats->has($type->id))
            ->map(fn (DocumentType $type): self => new self(
                type: $type,
                total: (int) $stats[$type->id]->total,
                firstYear: $stats[$type->id]->first_year ? (int) $stats[$type->id]->first_year : null,
                lastYear: $stats[$type->id]->last_year ? (int) $stats[$type->id]->last_year : null,
                lastPublishedOn: transform($stats[$type->id]->last_issued_at, fn (string $date): Carbon => Carbon::parse($date)),
                latestPeriod: $latestPeriods->get($type->id),
                years: $years->get($type->id, []),
            ))
            ->values();
    }

    /**
     * @param  Collection<int, DocumentType>  $types
     * @return Collection<int, list<int>>
     */
    private static function yearsByType(Collection $types): Collection
    {
        return Document::query()->published()->whereIn('document_type_id', $types->modelKeys())->whereNotNull('period_year')
            ->toBase()->distinct()->orderByDesc('period_year')
            ->get(['document_type_id', 'period_year'])
            ->groupBy('document_type_id')
            ->map(fn (Collection $rows): array => $rows->map(fn (object $row): int => (int) $row->period_year)->all());
    }

    /**
     * Latest month named in the titles published on each type's last publication day.
     *
     * @param  Collection<int, object>  $stats
     * @return Collection<int, CarbonInterface>
     */
    private static function latestPeriodsByType(Collection $stats): Collection
    {
        $lastDays = $stats->pluck('last_issued_at')->filter()->unique()->values();

        return Document::query()->published()
            ->whereIn('document_type_id', $stats->keys())
            ->whereIn('issued_at', $lastDays)
            ->get(['id', 'document_type_id', 'title', 'period_year', 'issued_at'])
            ->filter(fn (Document $document): bool => (bool) $document->issued_at?->isSameDay(Carbon::parse($stats[$document->document_type_id]->last_issued_at)))
            ->groupBy('document_type_id')
            ->map(fn (Collection $documents): ?CarbonInterface => $documents
                ->map(fn (Document $document): TitlePeriod => TitlePeriod::fromTitle($document->title, $document->period_year))
                ->filter(fn (TitlePeriod $period): bool => $period->hasMonth())
                ->map(fn (TitlePeriod $period): CarbonInterface => Carbon::create($period->year, $period->month))
                ->max())
            ->filter();
    }
}
