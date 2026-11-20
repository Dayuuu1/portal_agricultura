<?php

namespace App\Http\Controllers;

use App\Enums\DocumentSection;
use App\Models\Document;
use App\Models\DocumentType;
use App\Support\Documents\DocumentRelease;
use App\Support\Documents\DocumentSeries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $section = DocumentSection::tryFrom($request->string('seccion')->toString());
        $typeSlug = $request->string('tipo')->trim()->toString();
        $activeType = $typeSlug !== '' ? DocumentType::query()->where('slug', $typeSlug)->first() : null;
        $yearParam = $request->string('anio')->toString();
        $term = $request->string('q')->trim()->limit(100, '')->toString();

        // Without a type, search or year the page is an overview of the document types; "vista=lista" keeps the plain list.
        if ($typeSlug === '' && $term === '' && $yearParam === '' && $request->string('vista')->toString() !== 'lista') {
            return $this->overview($section);
        }

        $scope = fn (): Builder => Document::query()->published()
            ->when($section, fn (Builder $query) => $query->whereRelation('documentType', 'section', $section->value))
            ->when($typeSlug, fn (Builder $query) => $query->whereRelation('documentType', 'slug', $typeSlug));

        $yearCounts = $scope()->whereNotNull('period_year')
            ->selectRaw('period_year, count(*) as total')
            ->groupBy('period_year')->orderByDesc('period_year')
            ->pluck('total', 'period_year');

        $year = ctype_digit($yearParam) ? (int) $yearParam : null;

        // Periodic publications (statistics, resolutions…) open on their latest year, like the former portal.
        if ($yearParam === '' && $term === '' && $activeType?->group_by_year && $yearCounts->isNotEmpty()) {
            $year = (int) $yearCounts->keys()->first();
        }

        $statisticsYear = $year !== null && $term === '' && $activeType?->isMonthlyStatistics()
                ? $this->statisticsYear($scope()->where('period_year', $year)->get(), $year, $request->string('vista')->toString())
                : null;

        $documents = $statisticsYear ? null : $scope()->with('documentType')
            ->when($year, fn (Builder $query) => $query->where('period_year', $year))
            ->when($term, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('title', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")))
            ->orderByDesc('issued_at')->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'statisticsYear' => $statisticsYear,
            'lastPublishedOn' => $activeType ? transform($scope()->max('issued_at'), fn (string $date): Carbon => Carbon::parse($date)) : null,
            'activeType' => $activeType,
            'typesBySection' => DocumentType::query()
                ->withCount(['documents' => fn (Builder $query) => $query->published()])
                ->orderBy('sort_order')->get()
                ->groupBy(fn (DocumentType $type): string => $type->section->value),
            'yearCounts' => $yearCounts,
            'filters' => ['section' => $section, 'type' => $typeSlug, 'year' => $year, 'term' => $term],
        ]);
    }

    /**
     * Cards for every document type that has published documents, grouped by section, plus the latest uploads.
     */
    private function overview(?DocumentSection $section): View
    {
        $stats = Document::query()->published()->toBase()
            ->selectRaw('document_type_id, count(*) as total, min(period_year) as first_year, max(period_year) as last_year, max(issued_at) as last_issued_at')
            ->groupBy('document_type_id')
            ->get()
            ->keyBy('document_type_id');

        $typesBySection = DocumentType::query()
            ->when($section, fn (Builder $query) => $query->where('section', $section->value))
            ->orderBy('sort_order')->orderBy('name')
            ->get()
            ->filter(fn (DocumentType $type): bool => $stats->has($type->id))
            ->map(fn (DocumentType $type): array => [
                'type' => $type,
                'total' => (int) $stats[$type->id]->total,
                'firstYear' => $stats[$type->id]->first_year ? (int) $stats[$type->id]->first_year : null,
                'lastYear' => $stats[$type->id]->last_year ? (int) $stats[$type->id]->last_year : null,
                'lastPublishedOn' => transform($stats[$type->id]->last_issued_at, fn (string $date): Carbon => Carbon::parse($date)),
            ])
            ->groupBy(fn (array $entry): string => $entry['type']->section->value);

        return view('documents.overview', [
            'section' => $section,
            'typesBySection' => $typesBySection,
            'total' => $typesBySection->flatten(1)->sum('total'),
            'recent' => Document::query()->published()->with('documentType')
                ->when($section, fn (Builder $query) => $query->whereRelation('documentType', 'section', $section->value))
                ->orderByDesc('issued_at')->orderByDesc('id')
                ->limit(6)->get(),
        ]);
    }

    /**
     * One year of a statistics type, organized by month of publication or by indicator.
     *
     * @param  Collection<int, Document>  $documents
     * @return array{layout: string, total: int, releases: Collection<int, DocumentRelease>, series: Collection<int, DocumentSeries>, singles: Collection<int, Document>, offersSeries: bool}
     */
    private function statisticsYear(Collection $documents, int $year, string $requestedLayout): array
    {
        [$series, $singles] = DocumentSeries::groupFrom($documents, $year)
            ->partition(fn (DocumentSeries $series): bool => $series->count > 1);

        // Grouping by indicator only helps when the year has at least two indicators published repeatedly.
        $offersSeries = $series->count() >= 2;
        $layout = $offersSeries && $requestedLayout === 'indicador' ? 'indicador' : 'mes';

        return [
            'layout' => $layout,
            'total' => $documents->count(),
            'releases' => $layout === 'mes' ? DocumentRelease::groupFrom($documents) : collect(),
            'series' => $series->values(),
            'singles' => $singles->map(fn (DocumentSeries $single): Document => $single->others->first()['document'])->values(),
            'offersSeries' => $offersSeries,
        ];
    }

    public function show(Document $document): View
    {
        abort_unless($document->is_published, 404);

        $document->load(['documentType', 'organizationalUnit']);

        return view('documents.show', [
            'document' => $document,
            'related' => Document::query()->published()
                ->where('document_type_id', $document->document_type_id)
                ->whereKeyNot($document->getKey())
                ->latest('issued_at')->limit(5)->get(),
        ]);
    }

    public function download(Document $document): StreamedResponse|RedirectResponse
    {
        abort_unless($document->is_published && $document->hasFile(), 404);

        Document::query()->whereKey($document->getKey())->toBase()->increment('downloads');

        if (blank($document->file_path)) {
            return redirect()->away($document->external_url);
        }

        abort_unless(Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download(
            $document->file_path,
            $document->file_name ?: basename($document->file_path),
        );
    }
}
