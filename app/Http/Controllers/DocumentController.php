<?php

namespace App\Http\Controllers;

use App\Enums\DocumentSection;
use App\Models\Document;
use App\Models\DocumentType;
use App\Support\Documents\DocumentRelease;
use App\Support\Documents\DocumentSeries;
use App\Support\Documents\DocumentTypeEntry;
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
    public function index(Request $request): View|RedirectResponse
    {
        $section = DocumentSection::tryFrom($request->string('seccion')->toString());
        $typeSlug = $request->string('tipo')->trim()->toString();
        $activeType = $typeSlug !== '' ? DocumentType::query()->where('slug', $typeSlug)->first() : null;

        // Agrarian statistics have their own pages under «Información agraria»; links to the former addresses keep working.
        if ($activeType?->isAgrarianInformation()) {
            return redirect()->to($activeType->publicUrl($request->except('tipo', 'seccion')), 301);
        }

        // Without a type, search or year the page is an overview of the document types; "vista=lista" keeps the plain list.
        if ($typeSlug === '' && $request->string('q')->trim()->isEmpty() && $request->string('anio')->isEmpty() && $request->string('vista')->toString() !== 'lista') {
            return $section === DocumentSection::InformacionAgraria
                ? redirect()->route('agrarian-information', status: 301)
                : $this->overview($section);
        }

        return $this->listing($request, $section, $activeType, $typeSlug);
    }

    /**
     * Page of one agrarian statistics type (crop production, livestock, bulletins…), inside «Información agraria».
     */
    public function agrarianType(Request $request, DocumentType $documentType): View|RedirectResponse
    {
        if (! $documentType->isAgrarianInformation()) {
            return redirect()->to($documentType->publicUrl($request->query()), 301);
        }

        return $this->listing($request, null, $documentType, $documentType->slug);
    }

    /**
     * Documents of a type, a section or the whole portal, filtered by year and search term.
     */
    private function listing(Request $request, ?DocumentSection $section, ?DocumentType $activeType, string $typeSlug): View
    {
        $yearParam = $request->string('anio')->toString();
        $term = $request->string('q')->trim()->limit(100, '')->toString();

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

        // Agrarian statistics are browsed on their own, without the institutional document types around them.
        $isAgrarian = $section === DocumentSection::InformacionAgraria || (bool) $activeType?->isAgrarianInformation();

        return view('documents.index', [
            'documents' => $documents,
            'statisticsYear' => $statisticsYear,
            'lastPublishedOn' => $activeType ? transform($scope()->max('issued_at'), fn (string $date): Carbon => Carbon::parse($date)) : null,
            'activeType' => $activeType,
            'isAgrarian' => $isAgrarian,
            'typesBySection' => DocumentType::query()
                ->when($isAgrarian, fn (Builder $query) => $query->where('section', DocumentSection::InformacionAgraria))
                ->withCount(['documents' => fn (Builder $query) => $query->published()])
                ->orderBy('sort_order')->get()
                ->groupBy(fn (DocumentType $type): string => $type->section->value),
            'yearCounts' => $yearCounts,
            'filters' => ['section' => $section, 'type' => $typeSlug, 'year' => $year, 'term' => $term],
        ]);
    }

    /**
     * Cards for every institutional document type that has published documents, grouped by section, plus the latest
     * uploads. Agrarian statistics, most of the documents, only get a card leading to «Información agraria».
     */
    private function overview(?DocumentSection $section): View
    {
        $entries = DocumentTypeEntry::forTypes(DocumentType::query()
            ->when($section, fn (Builder $query) => $query->where('section', $section->value))
            ->orderBy('sort_order')->orderBy('name')
            ->get());

        [$agrarian, $institutional] = $entries->partition(fn (DocumentTypeEntry $entry): bool => $entry->type->isAgrarianInformation());

        return view('documents.overview', [
            'section' => $section,
            'typesBySection' => $institutional->groupBy(fn (DocumentTypeEntry $entry): string => $entry->type->section->value),
            'agrarian' => $agrarian->isEmpty() ? null : [
                'total' => $agrarian->sum('total'),
                'lastPublishedOn' => $agrarian->max('lastPublishedOn'),
            ],
            'total' => $entries->sum('total'),
            'recent' => Document::query()->published()->with('documentType')
                ->when($section, fn (Builder $query) => $query->whereRelation('documentType', 'section', $section->value))
                ->unless($section, fn (Builder $query) => $query->whereRelation('documentType', 'section', '!=', DocumentSection::InformacionAgraria->value))
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
