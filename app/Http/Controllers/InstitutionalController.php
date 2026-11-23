<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Document;
use App\Models\OrganizationalUnit;
use App\Models\Page;
use App\Support\OrganizationalUnits\OrganizationChart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class InstitutionalController extends Controller
{
    public function about(): View
    {
        return view('institutional.about', [
            'page' => Page::query()->published()->where('slug', 'nosotros')->first(),
            'director' => OrganizationalUnit::query()->active()->whereNull('parent_id')->orderBy('sort_order')->first(),
            'lineUnits' => OrganizationalUnit::query()->active()
                ->whereNotNull('parent_id')->whereIn('type', ['linea', 'apoyo'])
                ->whereHas('parent', fn (Builder $query) => $query->whereNull('parent_id'))
                ->orderBy('sort_order')->get(),
        ]);
    }

    public function organizationChart(): View
    {
        $roots = OrganizationalUnit::query()->active()->whereNull('parent_id')
            ->with(['children' => fn ($query) => $query->active()->with(['children' => fn ($inner) => $inner->active()])])
            ->orderBy('sort_order')->get();

        // The agrarian agencies are the deconcentrated bodies of the main head.
        $agencies = Agency::query()->active()->orderBy('sort_order')->get();

        return view('institutional.organization-chart', [
            'charts' => $roots->map(fn (OrganizationalUnit $root, int $index): OrganizationChart => OrganizationChart::for($root, $index === 0 ? $agencies : null)),
            'updatedAt' => ($lastChange = OrganizationalUnit::query()->max('updated_at')) ? Carbon::parse($lastChange) : null,
        ]);
    }

    public function staffDirectory(): View
    {
        return view('institutional.directory', [
            'units' => OrganizationalUnit::query()->active()
                ->where(fn (Builder $query) => $query->whereNull('parent_id')->orWhereHas('parent', fn (Builder $parent) => $parent->whereNull('parent_id')))
                ->orderByRaw('parent_id is not null')->orderBy('sort_order')->get(),
        ]);
    }

    public function unit(OrganizationalUnit $unit): View
    {
        abort_unless($unit->is_active, 404);

        return view('institutional.unit', [
            'unit' => $unit->load(['parent', 'children' => fn ($query) => $query->active()]),
            'documents' => Document::query()->published()->with('documentType')
                ->whereBelongsTo($unit)
                ->latest('issued_at')->limit(6)->get(),
        ]);
    }
}
