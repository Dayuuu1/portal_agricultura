<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintReason;
use App\Enums\IdentityDocumentType;
use App\Http\Requests\StoreComplaintRequest;
use App\Mail\ComplaintReceipt;
use App\Mail\ComplaintRegistered;
use App\Models\Agency;
use App\Models\Complaint;
use App\Models\OrganizationalUnit;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * Libro de Reclamaciones virtual (DS N° 007-2020-PCM).
 */
class ComplaintController extends Controller
{
    public function create(): View
    {
        return view('complaints.create', [
            'documentTypes' => IdentityDocumentType::cases(),
            'reasons' => ComplaintReason::cases(),
            'agencies' => Agency::query()->active()->orderBy('sort_order')->get(['id', 'name']),
            'units' => OrganizationalUnit::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        $isMinor = $request->boolean('is_minor');

        $complaint = Complaint::query()->create([
            ...$request->safe()->except(['attachments', 'truthful', 'privacy', 'website']),
            'is_minor' => $isMinor,
            'guardian_name' => $isMinor ? $request->validated('guardian_name') : null,
            'guardian_document_number' => $isMinor ? $request->validated('guardian_document_number') : null,
            'notify_by_email' => $request->boolean('notify_by_email'),
            'attachments' => $this->storeAttachments($request->file('attachments', [])),
            'ip_address' => $request->ip(),
        ]);

        $this->sendMails($complaint);

        return redirect()->to(URL::temporarySignedRoute('complaints.receipt', now()->addHours(2), $complaint));
    }

    /**
     * Copy of the record for the person who filed it (art. 15.6). The signed link keeps it private.
     */
    public function receipt(Complaint $complaint): Response
    {
        return response()
            ->view('complaints.receipt', ['complaint' => $complaint->load(['agency', 'organizationalUnit'])])
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Attachments hold personal data, so they stay on the private disk.
     *
     * @param  array<int, UploadedFile>  $files
     * @return list<array{path: string, name: string, size: int}>
     */
    private function storeAttachments(array $files): array
    {
        return collect($files)->map(fn (UploadedFile $file): array => [
            'path' => $file->storeAs('reclamaciones/'.now()->format('Y/m'), Str::uuid().'.'.$file->extension(), 'local'),
            'name' => Str::limit($file->getClientOriginalName(), 120, ''),
            'size' => $file->getSize(),
        ])->values()->all();
    }

    /**
     * A mail failure must never lose the claim: it is already saved and visible in the panel.
     */
    private function sendMails(Complaint $complaint): void
    {
        $mailbox = Setting::valueOf('complaints_email') ?: Setting::valueOf('email');

        try {
            if ($mailbox) {
                Mail::to($mailbox)->send(new ComplaintRegistered($complaint));
            }

            if (filled($complaint->email)) {
                Mail::to($complaint->email, $complaint->full_name)->send(new ComplaintReceipt($complaint));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
