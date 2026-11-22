<?php

namespace App\Http\Controllers\MesaPartes;

use App\Http\Controllers\Controller;
use App\Http\Requests\MesaPartes\CureSubmissionRequest;
use App\Http\Requests\MesaPartes\StoreSubmissionRequest;
use App\Models\Citizen;
use App\Models\OrganizationalUnit;
use App\Models\ProcedureType;
use App\Models\Submission;
use App\Support\MesaPartes\FilingClock;
use App\Support\MesaPartes\MesaPartesConfig;
use App\Support\MesaPartes\SubmissionWorkflow;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function create(): View
    {
        return view('mesa-partes.submissions.create', [
            'types' => ProcedureType::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'units' => OrganizationalUnit::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'withinHours' => FilingClock::isWithinHours(now()),
            'nextFilingDate' => FilingClock::filedOn(now()),
            'maxFileMb' => MesaPartesConfig::maxFileKilobytes() / 1024,
            'maxTotalMb' => MesaPartesConfig::maxTotalKilobytes() / 1024,
            'maxAttachments' => MesaPartesConfig::maxAttachments(),
        ]);
    }

    public function store(StoreSubmissionRequest $request): RedirectResponse
    {
        /** @var ProcedureType $type */
        $type = $request->procedureType();

        $submission = SubmissionWorkflow::file(
            $this->citizen($request),
            $type,
            $request->safe()->only(['subject', 'description', 'related_code', 'target_unit_id']),
            $request->file('principal'),
            $request->file('attachments', []),
            $request->file('payment_proof'),
            $request->ip(),
        );

        return redirect()->route('mesa-partes.submissions.receipt', $submission)
            ->with('status', 'Su documento fue recibido. Este es su cargo de recepción.');
    }

    public function show(Request $request, Submission $submission): View
    {
        $this->authorizeOwner($request, $submission);

        return view('mesa-partes.submissions.show', [
            'submission' => $submission->load([
                'procedureType', 'targetUnit', 'currentUnit', 'files',
                'movements' => fn ($query) => $query->where('is_public', true)->with('unit'),
            ]),
            'maxFileMb' => MesaPartesConfig::maxFileKilobytes() / 1024,
        ]);
    }

    /**
     * Cargo de recepción, ready to print or save as PDF from the browser.
     */
    public function receipt(Request $request, Submission $submission): Response
    {
        $this->authorizeOwner($request, $submission);

        return response()
            ->view('mesa-partes.submissions.receipt', ['submission' => $submission->load(['citizen', 'procedureType', 'targetUnit', 'files'])])
            ->header('Cache-Control', 'no-store, private');
    }

    public function cure(CureSubmissionRequest $request, Submission $submission): RedirectResponse
    {
        $this->authorizeOwner($request, $submission);

        try {
            SubmissionWorkflow::cure($submission, $request->validated('note'), $request->file('files', []));
        } catch (DomainException $exception) {
            return back()->withErrors(['note' => $exception->getMessage()]);
        }

        return redirect()->route('mesa-partes.submissions.show', $submission)
            ->with('status', 'Recibimos su subsanación. La mesa de partes la revisará.');
    }

    private function authorizeOwner(Request $request, Submission $submission): void
    {
        abort_unless($submission->citizen_id === $this->citizen($request)->id, 404);
    }

    private function citizen(Request $request): Citizen
    {
        /** @var Citizen */
        return $request->user('citizen');
    }
}
