<?php

namespace App\Http\Controllers;

use App\Enums\DocumentSection;
use App\Models\Document;
use App\Models\DocumentType;
use App\Support\Documents\DocumentTypeEntry;
use App\Support\Documents\TitlePeriod;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Home of the agrarian statistics: one card per topic (with the last month reported and its years), the reports of the
 * latest publication day, and apart, the series that are no longer updated.
 */
class AgrarianInformationController extends Controller
{
    /**
     * A topic without publications for this many months is shown apart, as an archive.
     */
    private const ARCHIVE_AFTER_MONTHS = 24;

    /**
     * Reports listed per topic in the latest publication; the topic's page has the rest.
     */
    private const LATEST_PER_TYPE = 6;

    public function __invoke(): View
    {
        $entries = DocumentTypeEntry::forTypes(
            DocumentType::query()->where('section', DocumentSection::InformacionAgraria)->orderBy('sort_order')->orderBy('name')->get(),
            withDetails: true,
        );

        [$archived, $current] = $entries->partition(
            fn (DocumentTypeEntry $entry): bool => (bool) $entry->lastPublishedOn?->lt(now()->subMonths(self::ARCHIVE_AFTER_MONTHS)),
        );

        $latestDay = $current->max('lastPublishedOn');

        return view('agrarian-information.index', [
            'topics' => $current->values(),
            'archived' => $archived->values(),
            'latestDay' => $latestDay,
            'latestRelease' => $latestDay ? $this->releaseOf($current, $latestDay) : collect(),
            'total' => $entries->sum('total'),
            'firstYear' => $entries->min('firstYear'),
        ]);
    }

    /**
     * Reports published on one day, by topic, newest reported month first.
     *
     * @param  Collection<int, DocumentTypeEntry>  $entries
     * @return Collection<int, array{type: DocumentType, documents: Collection<int, Document>, total: int}>
     */
    private function releaseOf(Collection $entries, CarbonInterface $day): Collection
    {
        $documentsByType = Document::query()->published()
            ->whereIn('document_type_id', $entries->map(fn (DocumentTypeEntry $entry): int => $entry->type->id))
            ->whereDate('issued_at', $day)
            ->get()
            ->groupBy('document_type_id');

        return $entries
            ->filter(fn (DocumentTypeEntry $entry): bool => $documentsByType->has($entry->type->id))
            ->map(fn (DocumentTypeEntry $entry): array => [
                'type' => $entry->type,
                'total' => $documentsByType[$entry->type->id]->count(),
                'documents' => $documentsByType[$entry->type->id]
                    ->sortBy([
                        fn (Document $a, Document $b): int => $this->reportedMonth($b) <=> $this->reportedMonth($a),
                        fn (Document $a, Document $b): int => $a->id <=> $b->id,
                    ])
                    ->take(self::LATEST_PER_TYPE)
                    ->values(),
            ])
            ->values();
    }

    /**
     * "2026-10" for a title that reports on October 2026; titles without a single month go last.
     */
    private function reportedMonth(Document $document): string
    {
        $period = TitlePeriod::fromTitle($document->title, $document->period_year);

        return sprintf('%04d-%02d', $period->year ?? 0, $period->month ?? 0);
    }
}
