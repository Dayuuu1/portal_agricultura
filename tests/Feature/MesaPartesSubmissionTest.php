<?php

namespace Tests\Feature;

use App\Enums\SubmissionFileKind;
use App\Enums\SubmissionStatus;
use App\Mail\MesaPartes\StaffSubmissionAlert;
use App\Mail\MesaPartes\SubmissionNotice;
use App\Mail\MesaPartes\SubmissionReceipt;
use App\Models\Citizen;
use App\Models\NonWorkingDay;
use App\Models\OrganizationalUnit;
use App\Models\ProcedureType;
use App\Models\Submission;
use App\Models\User;
use App\Support\MesaPartes\SubmissionWorkflow;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\InteractsWithMesaPartes;
use Tests\TestCase;

class MesaPartesSubmissionTest extends TestCase
{
    use InteractsWithMesaPartes, RefreshDatabase;

    private Citizen $citizen;

    private ProcedureType $request;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Mail::fake();
        $this->configureMesaPartes(['mpv_staff_email' => 'tramite@dramdd.gob.pe']);

        $this->citizen = Citizen::factory()->create(['email' => 'rosa@example.com']);
        $this->request = ProcedureType::query()->where('slug', 'solicitud-o-escrito')->sole();
    }

    private function file(array $overrides = [], array $files = []): TestResponse
    {
        return $this->actingAs($this->citizen, 'citizen')->post('/mesa-de-partes/mi-cuenta/tramites', [
            'procedure_type_id' => $this->request->id,
            'subject' => 'Solicito constancia de productor agrario',
            'description' => 'Requiero la constancia para acceder al programa de créditos agrarios.',
            'truthful' => '1',
            'principal' => $this->pdf('escrito.pdf', 3),
            ...$files,
            ...$overrides,
        ]);
    }

    public function test_a_filed_document_gets_its_number_cargo_terms_and_private_files(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 10:30'));

        $this->file(files: ['attachments' => [$this->pdf('dni.pdf'), UploadedFile::fake()->image('foto.jpg')]])
            ->assertRedirect();

        $submission = Submission::query()->with('files')->sole();
        $this->assertSame('MPV-2026-000001', $submission->code);
        $this->assertSame(SubmissionStatus::Recibido, $submission->status);
        $this->assertSame('2026-11-23', $submission->filed_on->toDateString());
        $this->assertSame('2027-01-08', $submission->due_on->toDateString(), '30 días hábiles sin el 8, 9 y 25 de diciembre ni el 1 de enero');
        $this->assertSame(5, $submission->folios);
        $this->assertCount(3, $submission->files);

        $principal = $submission->filesOfKind(SubmissionFileKind::Principal)->sole();
        $this->assertSame(64, strlen($principal->sha256));
        Storage::disk('local')->assertExists($principal->path);
        $this->assertStringStartsWith('mesa-de-partes/2026/11/', $principal->path);

        Mail::assertSent(SubmissionReceipt::class, fn (SubmissionReceipt $mail): bool => $mail->hasTo('rosa@example.com'));
        Mail::assertSent(StaffSubmissionAlert::class, fn (StaffSubmissionAlert $mail): bool => $mail->hasTo('tramite@dramdd.gob.pe'));
        $this->assertSame(1, $this->citizen->inbox()->count());
        $this->assertSame(['recibido'], $submission->movements()->pluck('action')->all());

        $this->get(route('mesa-partes.submissions.receipt', $submission))
            ->assertOk()
            ->assertSee(['MPV-2026-000001', 'Cargo de recepción', $principal->sha256, '23/11/2026']);
    }

    public function test_documents_sent_after_hours_or_on_non_working_days_are_filed_the_next_working_day(): void
    {
        NonWorkingDay::query()->create(['date' => '2026-11-23', 'description' => 'Día no laborable para el sector público']);

        $this->travelTo(Carbon::parse('2026-11-20 16:45'));
        $this->file()->assertRedirect();

        $this->travelTo(Carbon::parse('2026-11-21 09:00'));
        $this->file()->assertRedirect();

        $this->assertSame(['2026-11-24', '2026-11-24'], Submission::query()->orderBy('id')->get()->map(fn (Submission $s): string => $s->filed_on->toDateString())->all());

        $this->get(route('mesa-partes.submissions.receipt', Submission::query()->first()))
            ->assertSee('se considera presentado el primer día hábil siguiente');
    }

    public function test_files_are_checked_before_anything_is_filed(): void
    {
        $this->file(['principal' => UploadedFile::fake()->image('escrito.jpg')])->assertSessionHasErrors('principal');
        $this->file(['truthful' => null])->assertSessionHasErrors('truthful');

        $this->configureMesaPartes(['mpv_max_total_mb' => '1']);
        $this->file(files: ['attachments' => [UploadedFile::fake()->create('plano.pdf', 900, 'application/pdf'), UploadedFile::fake()->create('acta.pdf', 900, 'application/pdf')]])
            ->assertSessionHasErrors('attachments');

        $paid = ProcedureType::factory()->withFee()->create();
        $this->file(['procedure_type_id' => $paid->id])->assertSessionHasErrors('payment_proof');

        $this->assertSame(0, Submission::query()->count());
    }

    public function test_observation_cure_derivation_and_answer_reach_the_citizen(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 10:00'));
        $staff = User::factory()->admin()->create();
        $unit = OrganizationalUnit::factory()->create(['name' => 'Oficina de Estadística Agraria']);
        $this->file()->assertRedirect();
        $submission = Submission::query()->sole();

        SubmissionWorkflow::observe($submission, 'Falta la firma en el escrito.', $staff);
        $this->assertSame('2026-11-25', $submission->cure_deadline->toDateString());
        Mail::assertSent(SubmissionNotice::class, fn (SubmissionNotice $mail): bool => str_contains($mail->notification->title, 'observación'));

        $this->get(route('mesa-partes.submissions.show', $submission))
            ->assertOk()
            ->assertSee(['Falta la firma en el escrito.', 'Subsanar la observación']);

        $this->post(route('mesa-partes.submissions.cure', $submission), ['note' => 'Adjunto el escrito firmado.', 'files' => [$this->pdf('escrito-firmado.pdf', 2)]])
            ->assertRedirect(route('mesa-partes.submissions.show', $submission));
        $submission->refresh();
        $this->assertSame(SubmissionStatus::Subsanado, $submission->status);
        $this->assertSame(5, $submission->folios);
        Mail::assertSent(StaffSubmissionAlert::class, fn (StaffSubmissionAlert $mail): bool => $mail->isCure);

        SubmissionWorkflow::derive($submission, $unit, 'Atender con prioridad.', $staff);
        SubmissionWorkflow::answer($submission->refresh(), 'Se emitió la constancia solicitada.', $this->pdf('constancia.pdf'), $staff);

        $submission->refresh();
        $this->assertSame(SubmissionStatus::Atendido, $submission->status);
        $this->assertSame(['recibido', 'observado', 'subsanado', 'derivado', 'atendido'], $submission->movements()->pluck('action')->all());

        $this->get(route('mesa-partes.submissions.show', $submission))
            ->assertSee(['Respuesta de la entidad', 'Se emitió la constancia solicitada.', 'constancia.pdf', 'Oficina de Estadística Agraria'])
            ->assertDontSee('Atender con prioridad.');

        $response = $submission->filesOfKind(SubmissionFileKind::Respuesta)->sole();
        $this->get(route('mesa-partes.files.download', $response))->assertOk()->assertDownload('constancia.pdf');
    }

    public function test_formal_notices_are_emailed_only_with_the_citizens_authorization(): void
    {
        $this->citizen->update(['e_notifications_authorized_at' => null]);
        $this->file()->assertRedirect();
        $submission = Submission::query()->sole();

        SubmissionWorkflow::observe($submission, 'Falta la firma.', User::factory()->admin()->create());

        Mail::assertNotSent(SubmissionNotice::class, fn (SubmissionNotice $mail): bool => str_contains($mail->notification->title, 'observación'));
        $notice = $this->citizen->inbox()->where('title', 'like', '%observación%')->sole();
        $this->assertNull($notice->emailed_at);

        $this->get(route('mesa-partes.notifications.show', $notice))->assertOk()->assertSee('Falta la firma.');
        $this->assertNotNull($notice->fresh()->read_at);
    }

    public function test_a_document_is_declared_not_filed_only_after_the_term_to_cure_it(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 10:00'));
        $staff = User::factory()->admin()->create();
        $this->file()->assertRedirect();
        $submission = Submission::query()->sole();
        SubmissionWorkflow::observe($submission, 'Falta la firma.', $staff);

        try {
            SubmissionWorkflow::declareNotFiled($submission, null, $staff);
            $this->fail('Se declaró no presentado antes de que venza el plazo.');
        } catch (DomainException) {
        }

        $this->travelTo(Carbon::parse('2026-11-26 09:00'));
        $this->post(route('mesa-partes.submissions.cure', $submission), ['note' => 'Adjunto el escrito.'])->assertSessionHasErrors('note');

        SubmissionWorkflow::declareNotFiled($submission->refresh(), null, $staff);
        $this->assertSame(SubmissionStatus::NoPresentado, $submission->refresh()->status);
        $this->assertNotNull($submission->closed_at);
    }

    public function test_citizens_only_see_their_own_files(): void
    {
        $this->file()->assertRedirect();
        $submission = Submission::query()->with('files')->sole();
        $other = Citizen::factory()->create();

        $this->actingAs($other, 'citizen');
        $this->get(route('mesa-partes.submissions.show', $submission))->assertNotFound();
        $this->get(route('mesa-partes.submissions.receipt', $submission))->assertNotFound();
        $this->get(route('mesa-partes.files.download', $submission->files->first()))->assertNotFound();
    }

    public function test_anyone_can_follow_a_file_with_its_number_and_the_document_of_who_filed_it(): void
    {
        $this->file()->assertRedirect();
        $submission = Submission::query()->sole();
        SubmissionWorkflow::observe($submission, 'Dato privado de la observación.', User::factory()->admin()->create());

        $this->get('/mesa-de-partes/consulta?codigo='.strtolower($submission->code).'&documento='.$this->citizen->document_number)
            ->assertOk()
            ->assertSee(['Observado', 'Documento observado'])
            ->assertDontSee('Dato privado de la observación.');

        $this->get('/mesa-de-partes/consulta?codigo='.$submission->code.'&documento=00000000')
            ->assertOk()
            ->assertSee('No encontramos un expediente con esos datos');

        $this->configureMesaPartes(['mpv_public_lookup' => '0']);
        $this->get('/mesa-de-partes/consulta?codigo='.$submission->code.'&documento='.$this->citizen->document_number)->assertNotFound();
    }

    public function test_when_the_service_is_switched_off_no_new_documents_are_received_but_files_stay_available(): void
    {
        $this->file()->assertRedirect();
        $this->configureMesaPartes(['mpv_enabled' => '0']);

        $this->file()->assertRedirect(route('mesa-partes.index'));
        $this->assertSame(1, Submission::query()->count());
        $this->get('/mesa-de-partes/mi-cuenta')->assertOk()->assertSee('MPV-');
    }

    public function test_the_number_prefix_is_configurable(): void
    {
        $this->configureMesaPartes(['mpv_code_prefix' => 'DRA']);

        $this->file()->assertRedirect();

        $this->assertStringStartsWith('DRA-'.now()->year.'-', Submission::query()->sole()->code);
    }
}
