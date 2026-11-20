<?php

namespace App\Http\Controllers;

use App\Enums\DocumentSection;
use App\Enums\LinkGroup;
use App\Models\DocumentType;
use App\Models\Link;
use Illuminate\View\View;

class TransparencyController extends Controller
{
    public function __invoke(): View
    {
        return view('transparency.index', [
            'types' => DocumentType::query()
                ->whereIn('section', [DocumentSection::Gestion, DocumentSection::Normativa])
                ->withCount(['documents' => fn ($query) => $query->published()])
                ->with(['documents' => fn ($query) => $query->published()->latest('issued_at')->limit(3)])
                ->orderBy('sort_order')->get(),
            'stateLinks' => Link::query()->active()
                ->where(fn ($query) => $query->where('group', LinkGroup::PortalesEstado)->orWhere('name', 'RNSDD'))
                ->orderBy('sort_order')->get(),
        ]);
    }
}
