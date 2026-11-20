<?php

namespace App\Http\Controllers;

use App\Enums\DocumentSection;
use App\Models\DocumentType;
use Illuminate\View\View;

class AgrarianInformationController extends Controller
{
    public function __invoke(): View
    {
        return view('agrarian-information.index', [
            'types' => DocumentType::query()
                ->where('section', DocumentSection::InformacionAgraria)
                ->with(['documents' => fn ($query) => $query->published()->latest('issued_at')->limit(6)])
                ->orderBy('sort_order')->get(),
        ]);
    }
}
