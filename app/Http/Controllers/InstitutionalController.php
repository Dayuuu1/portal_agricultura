<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\OrganizationalUnit;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
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
        return view('institutional.organization-chart', [
            'roots' => OrganizationalUnit::query()->active()->whereNull('parent_id')
                ->with(['children' => fn ($query) => $query->active()->with(['children' => fn ($inner) => $inner->active()])])
                ->orderBy('sort_order')->get(),
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
