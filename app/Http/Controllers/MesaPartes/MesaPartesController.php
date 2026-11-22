<?php

namespace App\Http\Controllers\MesaPartes;

use App\Http\Controllers\Controller;
use App\Models\ProcedureType;
use App\Models\Submission;
use App\Support\MesaPartes\FilingClock;
use App\Support\MesaPartes\MesaPartesConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Public face of the Mesa de Partes Virtual: what can be filed, the schedule rule, and the lookup of a file
 * without an account.
 */
class MesaPartesController extends Controller
{
    public function index(): View
    {
        return view('mesa-partes.index', [
            'enabled' => MesaPartesConfig::enabled(),
            'types' => ProcedureType::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'withinHours' => FilingClock::isWithinHours(now()),
            'nextFilingDate' => FilingClock::filedOn(now()),
            'citizen' => Auth::guard('citizen')->user(),
        ]);
    }

    /**
     * Status and public route of a file, with its number and the filer's document (no files nor personal data).
     */
    public function lookup(Request $request): View
    {
        abort_unless(MesaPartesConfig::publicLookup(), 404);

        $code = strtoupper(trim((string) $request->query('codigo')));
        $document = strtoupper(trim((string) $request->query('documento')));
        $searched = $code !== '' && $document !== '';

        $submission = $searched && strlen($code) <= 30 && strlen($document) <= 20
            ? Submission::query()
                ->where('code', $code)
                ->whereHas('citizen', fn ($query) => $query->where('document_number', $document))
                ->with(['procedureType', 'movements' => fn ($query) => $query->where('is_public', true)->with('unit')])
                ->first()
            : null;

        return view('mesa-partes.lookup', compact('code', 'document', 'searched', 'submission'));
    }
}
