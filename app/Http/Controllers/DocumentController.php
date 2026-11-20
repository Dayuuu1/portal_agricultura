<?php

namespace App\Http\Controllers;

use App\Enums\DocumentSection;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $documents = $scope()->with('documentType')
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
            'activeType' => $activeType,
            'typesBySection' => DocumentType::query()
                ->withCount(['documents' => fn (Builder $query) => $query->published()])
                ->orderBy('sort_order')->get()
                ->groupBy(fn (DocumentType $type): string => $type->section->value),
            'yearCounts' => $yearCounts,
            'filters' => ['section' => $section, 'type' => $typeSlug, 'year' => $year, 'term' => $term],
        ]);
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
