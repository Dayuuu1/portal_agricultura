<?php

namespace Tests\Feature;

use App\Enums\SubmissionStatus;
use App\Filament\Pages\MesaPartesSettings;
use App\Filament\Resources\Citizens\Pages\ViewCitizen;
use App\Filament\Resources\ProcedureTypes\Pages\ManageProcedureTypes;
use App\Filament\Resources\Submissions\Pages\ListSubmissions;
use App\Filament\Resources\Submissions\Pages\ViewSubmission;
use App\Filament\Widgets\PendingTasks;
use App\Models\ActivityLog;
use App\Models\Citizen;
use App\Models\OrganizationalUnit;
use App\Models\ProcedureType;
use App\Models\Setting;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use App\Support\MesaPartes\SubmissionWorkflow;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithMesaPartes;
use Tests\TestCase;

class MesaPartesPanelTest extends TestCase
{
    use InteractsWithMesaPartes, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Mail::fake();
        $this->configureMesaPartes();
    }

    private function filedSubmission(): Submission
    {
        return SubmissionWorkflow::file(
            Citizen::factory()->create(),
            ProcedureType::query()->where('slug', 'solicitud-o-escrito')->sole(),
            ['subject' => 'Solicito constancia', 'description' => 'Para un programa de créditos agrarios.'],
            $this->pdf(),
        );
    }

    private function mesaDePartesStaff(): User
    {
        return User::factory()->withPermissions(['mesa_partes' => ['view', 'update']])->create();
    }

    public function test_staff_review_new_documents_in_the_inbox(): void
    {
        $submission = $this->filedSubmission();
        $this->actingAs($this->mesaDePartesStaff());

        $this->get('/admin/mesa-de-partes')->assertOk()->assertSee($submission->code);
        $this->get('/admin/mesa-de-partes/'.$submission->code)->assertOk()->assertSee(['Solicito constancia', 'Hoja de ruta', 'Documento recibido']);

        Livewire::test(ListSubmissions::class)
            ->assertCanSeeTableRecords([$submission]);

        Livewire::test(PendingTasks::class)->assertSee('Documentos por revisar en mesa de partes');
    }

    public function test_staff_observe_derive_and_answer_from_the_record(): void
    {
        $submission = $this->filedSubmission();
        $unit = OrganizationalUnit::factory()->create();
        $this->actingAs($this->mesaDePartesStaff());

        Livewire::test(ViewSubmission::class, ['record' => $submission->code])
            ->callAction('observe', data: ['observation' => 'Falta la firma.'])
            ->assertHasNoFormErrors();
        $this->assertSame(SubmissionStatus::Observado, $submission->refresh()->status);

        SubmissionWorkflow::cure($submission, 'Adjunto el escrito firmado.');

        Livewire::test(ViewSubmission::class, ['record' => $submission->code])
            ->callAction('derive', data: ['unit_id' => $unit->id, 'note' => 'Atender.'])
            ->assertHasNoFormErrors()
            ->callAction('answer', data: ['response' => 'Se emitió la constancia.'])
            ->assertHasNoFormErrors();

        $submission->refresh();
        $this->assertSame(SubmissionStatus::Atendido, $submission->status);
        $this->assertSame($unit->id, $submission->current_unit_id);
    }

    public function test_staff_download_files_only_with_permission(): void
    {
        $file = $this->filedSubmission()->files()->sole();

        $this->actingAs($this->mesaDePartesStaff())->get(route('admin.mesa-partes.file', $file))->assertOk()->assertDownload('escrito.pdf');

        $this->actingAs(User::factory()->withPermissions(['noticias' => ['view']])->create())
            ->get(route('admin.mesa-partes.file', $file))->assertForbidden();
        $this->get('/admin/mesa-de-partes')->assertForbidden();
    }

    public function test_viewers_cannot_change_expedientes_nor_suspend_accounts(): void
    {
        $submission = $this->filedSubmission();
        $this->actingAs(User::factory()->withPermissions(['mesa_partes' => ['view']])->create());

        Livewire::test(ViewSubmission::class, ['record' => $submission->code])
            ->assertActionHidden('observe')
            ->assertActionHidden('derive');

        Livewire::test(ViewCitizen::class, ['record' => $submission->citizen_id])
            ->assertActionHidden('toggleActive')
            ->assertActionHidden('verifyIdentity');
    }

    public function test_staff_record_who_checked_the_identity_behind_an_account(): void
    {
        $submission = $this->filedSubmission();
        $staff = $this->mesaDePartesStaff();
        $this->actingAs($staff);

        Livewire::test(ViewSubmission::class, ['record' => $submission->code])->assertSee('Sin verificar');

        Livewire::test(ViewCitizen::class, ['record' => $submission->citizen_id])->callAction('verifyIdentity');

        $citizen = $submission->citizen->fresh();
        $this->assertTrue($citizen->hasVerifiedIdentity());
        $this->assertTrue($citizen->identityVerifier->is($staff));

        Livewire::test(ViewSubmission::class, ['record' => $submission->code])->assertDontSee('Sin verificar');
    }

    public function test_the_activity_log_records_changes_of_personal_data_without_the_data(): void
    {
        $citizen = Citizen::factory()->create(['phone' => '987654321']);

        $citizen->update(['phone' => '912345678', 'address' => 'Av. Madre de Dios 456']);

        $changes = ActivityLog::query()->where('event', 'updated')->whereMorphedTo('subject', $citizen)->sole()->changes;
        $this->assertSame(['old' => '(dato protegido)', 'new' => '(dato protegido)'], $changes['phone']);
        $this->assertArrayHasKey('address', $changes);
        $this->assertStringNotContainsString('912345678', (string) json_encode($changes));
    }

    public function test_the_service_rules_are_configured_from_the_panel(): void
    {
        $this->actingAs(User::factory()->withPermissions(['mesa_partes_config' => ['view', 'create', 'update', 'delete']])->create());

        Livewire::test(MesaPartesSettings::class)
            ->fillForm(['mpv_closing_time' => '07:00'])
            ->call('save')
            ->assertHasFormErrors(['mpv_closing_time']);

        Livewire::test(MesaPartesSettings::class)
            ->fillForm(['mpv_enabled' => false, 'mpv_closing_time' => '16:30', 'mpv_cure_days' => 2, 'mpv_code_prefix' => 'dra', 'mpv_approval' => 'R.D. N° 045-2026-GOREMAD/DRDAR'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('0', Setting::query()->where('key', 'mpv_enabled')->value('value'));
        $this->assertSame('16:30', Setting::query()->where('key', 'mpv_closing_time')->value('value'));
        $this->assertSame('DRA', Setting::query()->where('key', 'mpv_code_prefix')->value('value'));

        Livewire::test(ManageProcedureTypes::class)
            ->callAction('create', data: ['name' => 'Constancia de productor agrario', 'response_days' => 7, 'fee' => 15, 'requires_payment_proof' => true, 'is_active' => true])
            ->assertHasNoFormErrors();
        $this->assertTrue(ProcedureType::query()->where('slug', 'constancia-de-productor-agrario')->sole()->requires_payment_proof);
    }

    public function test_the_general_settings_page_does_not_mix_in_the_mesa_de_partes_rules(): void
    {
        $this->seed(SettingSeeder::class);
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/datos-del-portal')->assertOk()->assertDontSee('Prefijo del número de expediente');
        $this->get('/admin/mesa-de-partes/configuracion')->assertOk()->assertSee('Prefijo del número de expediente');
    }

    public function test_files_on_the_private_disk_are_not_public(): void
    {
        $file = $this->filedSubmission()->files()->sole();

        $this->assertSame(SubmissionFile::DISK, 'local');
        $this->assertFalse(Storage::disk('public')->exists($file->path));
    }
}
