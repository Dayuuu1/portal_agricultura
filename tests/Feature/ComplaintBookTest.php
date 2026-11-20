<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\Complaints\Pages\ViewComplaint;
use App\Filament\Widgets\PendingTasks;
use App\Mail\ComplaintAnswered;
use App\Mail\ComplaintReceipt;
use App\Mail\ComplaintRegistered;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ComplaintBookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validClaim(array $overrides = []): array
    {
        return [
            'full_name' => 'Rosa Quispe Mamani',
            'document_type' => 'dni',
            'document_number' => '45678912',
            'address' => 'Jr. Loreto 123, Tambopata',
            'phone' => '987654321',
            'email' => 'rosa@example.com',
            'incident_date' => now()->subDays(2)->toDateString(),
            'reason' => 'tiempo-atencion',
            'description' => 'Esperé más de tres horas para ser atendida en la oficina de saneamiento.',
            'notify_by_email' => '1',
            'truthful' => '1',
            'privacy' => '1',
            ...$overrides,
        ];
    }

    public function test_complaint_book_page_explains_the_legal_conditions(): void
    {
        $this->get('/libro-de-reclamaciones')
            ->assertOk()
            ->assertSee('Hoja de reclamación virtual')
            ->assertSee('30 días hábiles')
            ->assertSee('gratuito')
            ->assertSee('Decreto Supremo N° 007-2020-PCM')
            ->assertSeeInOrder(['Identificación de la persona', 'Identificación de la atención brindada', 'Detalle del reclamo', 'Notificación de la respuesta']);

        $this->get('/')->assertSee(route('complaints.create'), false);
    }

    public function test_a_claim_is_registered_mailed_and_receipted(): void
    {
        Mail::fake();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 19)->setTime(10, 0));

        $response = $this->post('/libro-de-reclamaciones', $this->validClaim([
            'attachments' => [UploadedFile::fake()->create('ticket.pdf', 100, 'application/pdf')],
        ]));

        $complaint = Complaint::query()->sole();
        $this->assertSame('LR-2026-000001', $complaint->code);
        $this->assertSame(ComplaintStatus::Registrado, $complaint->status);
        $this->assertSame('2027-01-06', $complaint->due_on->toDateString());
        $this->assertSame('ticket.pdf', $complaint->attachments[0]['name']);
        Storage::disk('local')->assertExists($complaint->attachments[0]['path']);

        Mail::assertSent(ComplaintRegistered::class, fn (ComplaintRegistered $mail): bool => $mail->hasTo('info@dramdd.gob.pe')
            && $mail->hasReplyTo('rosa@example.com')
            && count($mail->attachments()) === 1);
        Mail::assertSent(ComplaintReceipt::class, fn (ComplaintReceipt $mail): bool => $mail->hasTo('rosa@example.com'));

        $response->assertRedirect();
        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('LR-2026-000001')
            ->assertSee('Su reclamo fue registrado')
            ->assertSee('noindex', false);
    }

    public function test_codes_are_correlative_within_each_year(): void
    {
        $this->travelTo(now()->setDate(2026, 12, 30));
        Complaint::factory()->count(2)->create();
        $this->travelTo(now()->setDate(2027, 1, 4));
        $next = Complaint::factory()->create();

        $this->assertSame(['LR-2026-000001', 'LR-2026-000002'], Complaint::query()->where('code', 'like', 'LR-2026-%')->orderBy('code')->pluck('code')->all());
        $this->assertSame('LR-2027-000001', $next->code);
    }

    public function test_the_receipt_is_only_reachable_through_its_signed_link(): void
    {
        $complaint = Complaint::factory()->create();

        $this->get("/libro-de-reclamaciones/constancia/{$complaint->code}")->assertForbidden();
        $this->get(URL::temporarySignedRoute('complaints.receipt', now()->addHour(), $complaint))->assertOk()->assertSee($complaint->code);
    }

    public function test_the_form_validates_the_required_data(): void
    {
        $this->post('/libro-de-reclamaciones', $this->validClaim([
            'document_number' => '1234',
            'email' => '',
            'is_minor' => '1',
            'incident_date' => now()->addDay()->toDateString(),
            'description' => 'Muy corto',
            'truthful' => null,
        ]))->assertSessionHasErrors(['document_number', 'email', 'guardian_name', 'guardian_document_number', 'incident_date', 'description', 'truthful']);

        $this->post('/libro-de-reclamaciones', $this->validClaim([
            'attachments' => [UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream')],
        ]))->assertSessionHasErrors('attachments.0');

        $this->assertSame(0, Complaint::query()->count());
    }

    public function test_email_is_optional_when_the_answer_goes_to_the_home_address(): void
    {
        Mail::fake();

        $this->post('/libro-de-reclamaciones', $this->validClaim(['email' => '', 'notify_by_email' => null]))->assertSessionHasNoErrors();

        $this->assertFalse(Complaint::query()->sole()->canBeNotifiedByEmail());
        Mail::assertSent(ComplaintRegistered::class);
        Mail::assertNotSent(ComplaintReceipt::class);
    }

    public function test_bots_filling_the_hidden_field_are_rejected(): void
    {
        $this->post('/libro-de-reclamaciones', $this->validClaim(['website' => 'http://spam.example']))->assertSessionHasErrors('website');

        $this->assertSame(0, Complaint::query()->count());
    }

    public function test_staff_answer_the_claim_and_the_person_is_notified_by_email(): void
    {
        Mail::fake();
        $complaint = Complaint::factory()->create(['email' => 'rosa@example.com', 'notify_by_email' => true]);
        $agent = User::factory()->withPermissions(['reclamaciones' => ['view', 'update']])->create();
        $this->actingAs($agent);

        Livewire::test(ViewComplaint::class, ['record' => $complaint->getRouteKey()])
            ->callAction('respond', data: ['status' => 'aceptado', 'response' => 'Se reforzó el personal de atención en ventanilla.'])
            ->assertHasNoActionErrors();

        $complaint->refresh();
        $this->assertSame(ComplaintStatus::Aceptado, $complaint->status);
        $this->assertSame($agent->id, $complaint->responded_by);
        Mail::assertSent(ComplaintAnswered::class, fn (ComplaintAnswered $mail): bool => $mail->hasTo('rosa@example.com'));
    }

    public function test_answers_are_not_emailed_without_the_person_authorization(): void
    {
        Mail::fake();
        $complaint = Complaint::factory()->create(['notify_by_email' => false]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewComplaint::class, ['record' => $complaint->getRouteKey()])
            ->callAction('respond', data: ['status' => 'denegado', 'response' => 'El servicio se brindó dentro del horario establecido.'])
            ->assertHasNoActionErrors();

        $this->assertSame(ComplaintStatus::Denegado, $complaint->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_claims_are_official_records_that_nobody_can_delete(): void
    {
        $complaint = Complaint::factory()->create();
        $admin = User::factory()->admin()->create();
        $reader = User::factory()->withPermissions(['reclamaciones' => ['view']])->create();

        $this->assertFalse($admin->can('delete', $complaint));
        $this->assertFalse($admin->can('create', Complaint::class));
        $this->assertTrue($reader->can('view', $complaint));
        $this->assertFalse($reader->can('update', $complaint));

        $this->actingAs($reader)->get('/admin/libro-de-reclamaciones')->assertOk()->assertSee($complaint->code);
        $this->actingAs(User::factory()->withPermissions(['noticias' => ['view']])->create())->get('/admin/libro-de-reclamaciones')->assertForbidden();
    }

    public function test_overdue_claims_are_flagged_on_the_dashboard(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1));
        Complaint::factory()->create();
        $this->travelBack();
        $this->travelTo(now()->setDate(2026, 11, 19));

        $this->actingAs(User::factory()->withPermissions(['reclamaciones' => ['view', 'update']])->create());

        Livewire::test(PendingTasks::class)
            ->assertSee('Reclamos por responder')
            ->assertSee('plazo legal de 30 días hábiles vencido');
    }

    public function test_entity_mail_renders_the_full_claim(): void
    {
        $complaint = Complaint::factory()->create(['full_name' => 'Rosa Quispe Mamani', 'description' => 'Me atendieron con demora.']);

        (new ComplaintRegistered($complaint))
            ->assertSeeInHtml($complaint->code)
            ->assertSeeInHtml('Rosa Quispe Mamani')
            ->assertSeeInHtml('Me atendieron con demora.')
            ->assertSeeInHtml('30 días hábiles');
    }
}
