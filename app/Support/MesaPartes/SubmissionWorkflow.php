<?php

namespace App\Support\MesaPartes;

use App\Enums\SubmissionFileKind;
use App\Enums\SubmissionStatus;
use App\Mail\MesaPartes\StaffSubmissionAlert;
use App\Mail\MesaPartes\SubmissionReceipt;
use App\Models\Citizen;
use App\Models\OrganizationalUnit;
use App\Models\ProcedureType;
use App\Models\Submission;
use App\Models\User;
use App\Support\BusinessDays;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Every change of an expediente goes through here, so each one leaves its step on the route («hoja de ruta»),
 * the activity log and, when the citizen must know, a message in their inbox.
 */
final class SubmissionWorkflow
{
    /**
     * Receives a document: assigns the number, the filing date and the attention term, stores the files and
     * sends the cargo (TUO de la Ley N° 27444; Ley N° 31170).
     *
     * @param  array{subject: string, description: string, related_code?: string|null, target_unit_id?: int|string|null}  $data
     * @param  list<UploadedFile>  $attachments
     */
    public static function file(Citizen $citizen, ProcedureType $type, array $data, UploadedFile $principal, array $attachments = [], ?UploadedFile $paymentProof = null, ?string $ipAddress = null): Submission
    {
        $submittedAt = now();
        $filedOn = FilingClock::filedOn($submittedAt);
        $attentionDays = $type->attentionDays();

        $submission = DB::transaction(function () use ($citizen, $type, $data, $principal, $attachments, $paymentProof, $ipAddress, $submittedAt, $filedOn, $attentionDays): Submission {
            $submission = Submission::query()->create([
                'code' => Submission::nextCode($submittedAt->year),
                'citizen_id' => $citizen->id,
                'procedure_type_id' => $type->id,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'related_code' => $data['related_code'] ?? null,
                'target_unit_id' => $data['target_unit_id'] ?? $type->default_unit_id,
                'status' => SubmissionStatus::Recibido,
                'submitted_at' => $submittedAt,
                'filed_on' => $filedOn,
                'due_on' => $attentionDays ? BusinessDays::after($filedOn, $attentionDays) : null,
                'ip_address' => $ipAddress,
            ]);

            $files = [SubmissionFileStore::store($submission, $principal, SubmissionFileKind::Principal)];
            foreach ($attachments as $attachment) {
                $files[] = SubmissionFileStore::store($submission, $attachment, SubmissionFileKind::Anexo);
            }
            if ($paymentProof) {
                $files[] = SubmissionFileStore::store($submission, $paymentProof, SubmissionFileKind::Comprobante);
            }

            $submission->forceFill(['folios' => array_sum(array_map(fn ($file): int => $file->pages, $files))])->save();
            self::move($submission, 'recibido', null, SubmissionStatus::Recibido);

            return $submission;
        });

        $submission->load(['citizen', 'procedureType', 'files']);
        self::mail($citizen->email, new SubmissionReceipt($submission));
        self::alertStaff($submission);
        CitizenNotifier::deposit($submission, "Recibimos su documento {$submission->code}",
            "Su documento «{$submission->subject}» fue recibido el {$submission->submitted_at->format('d/m/Y H:i')} y se considera presentado el {$submission->filed_on->format('d/m/Y')}. La mesa de partes lo revisará y le informaremos por este medio.",
            formal: false);

        return $submission;
    }

    /**
     * Something is missing: the citizen gets the reason and a term to complete it (art. 136).
     */
    public static function observe(Submission $submission, string $observation, User $user): Submission
    {
        self::ensureStatus($submission, [SubmissionStatus::Recibido, SubmissionStatus::Subsanado]);

        $cureDays = MesaPartesConfig::cureDays();
        $deadline = BusinessDays::after(today(), $cureDays);

        DB::transaction(function () use ($submission, $observation, $user, $deadline): void {
            $from = $submission->status;
            $submission->update([
                'status' => SubmissionStatus::Observado,
                'observation' => $observation,
                'observed_at' => now(),
                'cure_deadline' => $deadline,
            ]);
            self::move($submission, 'observado', $from, SubmissionStatus::Observado, note: $observation, user: $user);
        });

        CitizenNotifier::deposit($submission, "Su documento {$submission->code} tiene una observación",
            "Observación de la mesa de partes:\n{$observation}\n\nPuede subsanarla desde su cuenta hasta el {$deadline->translatedFormat('j \d\e F \d\e Y')} ({$cureDays} días hábiles). Si no la subsana dentro del plazo, el documento se considerará no presentado.",
        );

        return $submission;
    }

    /**
     * The citizen completes the observation with a note and, optionally, new files.
     *
     * @param  list<UploadedFile>  $files
     */
    public static function cure(Submission $submission, string $note, array $files = []): Submission
    {
        if (! $submission->canBeCured()) {
            throw new DomainException('El plazo para subsanar este documento ya venció o no tiene observaciones pendientes.');
        }

        DB::transaction(function () use ($submission, $note, $files): void {
            $pages = 0;
            foreach ($files as $file) {
                $pages += SubmissionFileStore::store($submission, $file, SubmissionFileKind::Subsanacion)->pages;
            }

            $submission->update([
                'status' => SubmissionStatus::Subsanado,
                'cured_at' => now(),
                'folios' => $submission->folios + $pages,
            ]);
            self::move($submission, 'subsanado', SubmissionStatus::Observado, SubmissionStatus::Subsanado, note: $note);
        });

        self::alertStaff($submission, isCure: true);
        CitizenNotifier::deposit($submission, "Recibimos su subsanación del documento {$submission->code}",
            'La mesa de partes revisará la información que envió y continuará con el trámite.', formal: false);

        return $submission;
    }

    /**
     * Admits the document and sends it to the office that will attend it, or moves it to another office.
     */
    public static function derive(Submission $submission, OrganizationalUnit $unit, ?string $note, User $user): Submission
    {
        self::ensureStatus($submission, [SubmissionStatus::Recibido, SubmissionStatus::Subsanado, SubmissionStatus::Derivado, SubmissionStatus::EnAtencion]);

        DB::transaction(function () use ($submission, $unit, $note, $user): void {
            $from = $submission->status;
            $submission->update(['status' => SubmissionStatus::Derivado, 'current_unit_id' => $unit->id]);
            self::move($submission, 'derivado', $from, SubmissionStatus::Derivado, unitId: $unit->id, note: $note, user: $user);
        });

        CitizenNotifier::deposit($submission, "Su documento {$submission->code} fue derivado",
            "Su documento fue admitido y derivado a: {$unit->name}. Esta oficina se encargará de atenderlo.");

        return $submission;
    }

    public static function startAttention(Submission $submission, User $user): Submission
    {
        self::ensureStatus($submission, [SubmissionStatus::Derivado]);

        DB::transaction(function () use ($submission, $user): void {
            $submission->update(['status' => SubmissionStatus::EnAtencion]);
            self::move($submission, 'en-atencion', SubmissionStatus::Derivado, SubmissionStatus::EnAtencion, unitId: $submission->current_unit_id, user: $user);
        });

        return $submission;
    }

    /**
     * Registers the entity's answer (text and, optionally, the signed document) and notifies it.
     */
    public static function answer(Submission $submission, string $response, ?UploadedFile $document, User $user): Submission
    {
        self::ensureStatus($submission, [SubmissionStatus::Recibido, SubmissionStatus::Subsanado, SubmissionStatus::Derivado, SubmissionStatus::EnAtencion]);

        DB::transaction(function () use ($submission, $response, $document, $user): void {
            if ($document) {
                SubmissionFileStore::store($submission, $document, SubmissionFileKind::Respuesta, $user->id);
            }

            $from = $submission->status;
            $submission->update([
                'status' => SubmissionStatus::Atendido,
                'response' => $response,
                'answered_at' => now(),
                'closed_at' => now(),
            ]);
            self::move($submission, 'atendido', $from, SubmissionStatus::Atendido, unitId: $submission->current_unit_id, note: $response, user: $user);
        });

        CitizenNotifier::deposit($submission, "Respuesta a su documento {$submission->code}",
            $response.($document ? "\n\nEl documento de respuesta está disponible para descargar en su cuenta." : ''));

        return $submission;
    }

    /**
     * The term to complete the observation passed without an answer: the document is considered not filed (art. 136).
     */
    public static function declareNotFiled(Submission $submission, ?string $note, User $user): Submission
    {
        self::ensureStatus($submission, [SubmissionStatus::Observado]);

        if ($submission->cure_deadline?->endOfDay()->isFuture()) {
            throw new DomainException('Todavía está corriendo el plazo para subsanar.');
        }

        DB::transaction(function () use ($submission, $note, $user): void {
            $submission->update(['status' => SubmissionStatus::NoPresentado, 'closed_at' => now()]);
            self::move($submission, 'no-presentado', SubmissionStatus::Observado, SubmissionStatus::NoPresentado, note: $note, user: $user);
        });

        CitizenNotifier::deposit($submission, "Su documento {$submission->code} se tuvo por no presentado",
            'La observación no fue subsanada dentro del plazo, por lo que el documento se considera no presentado (TUO de la Ley N° 27444, art. 136). Puede presentarlo nuevamente con la información completa.'
            .($note ? "\n\n{$note}" : ''));

        return $submission;
    }

    public static function archive(Submission $submission, string $reason, User $user): Submission
    {
        if (! $submission->status->isOpen()) {
            throw new DomainException('El expediente ya está cerrado.');
        }

        DB::transaction(function () use ($submission, $reason, $user): void {
            $from = $submission->status;
            $submission->update(['status' => SubmissionStatus::Archivado, 'closed_at' => now()]);
            self::move($submission, 'archivado', $from, SubmissionStatus::Archivado, note: $reason, user: $user);
        });

        CitizenNotifier::deposit($submission, "Su documento {$submission->code} fue archivado", "Motivo: {$reason}");

        return $submission;
    }

    /**
     * Internal note on the route; the citizen never sees it.
     */
    public static function addNote(Submission $submission, string $note, User $user): void
    {
        self::move($submission, 'nota', $submission->status, $submission->status, unitId: $submission->current_unit_id, note: $note, user: $user, isPublic: false);
    }

    private static function move(Submission $submission, string $action, ?SubmissionStatus $from, SubmissionStatus $to, ?int $unitId = null, ?string $note = null, ?User $user = null, bool $isPublic = true): void
    {
        $submission->movements()->create([
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'unit_id' => $unitId,
            'user_id' => $user?->id,
            'note' => $note,
            'is_public' => $isPublic,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  list<SubmissionStatus>  $allowed
     */
    private static function ensureStatus(Submission $submission, array $allowed): void
    {
        if (! in_array($submission->status, $allowed, true)) {
            throw new DomainException("La acción no corresponde a un expediente en estado «{$submission->status->getLabel()}».");
        }
    }

    private static function alertStaff(Submission $submission, bool $isCure = false): void
    {
        if ($mailbox = MesaPartesConfig::staffEmail()) {
            self::mail($mailbox, new StaffSubmissionAlert($submission->loadMissing(['citizen', 'procedureType']), $isCure));
        }
    }

    /**
     * A mail failure must never lose the expediente: it is already saved and visible in the panel and the account.
     */
    private static function mail(string $to, Mailable $mailable): void
    {
        try {
            Mail::to($to)->send($mailable);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
