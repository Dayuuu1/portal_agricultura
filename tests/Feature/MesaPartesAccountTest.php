<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Notifications\MesaPartes\ResetCitizenPassword;
use App\Notifications\MesaPartes\VerifyCitizenEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\InteractsWithMesaPartes;
use Tests\TestCase;

class MesaPartesAccountTest extends TestCase
{
    use InteractsWithMesaPartes, RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function registration(array $overrides = []): array
    {
        return [
            'person_type' => 'natural',
            'document_type' => 'dni',
            'document_number' => '45678912',
            'first_name' => 'Rosa',
            'last_name' => 'Quispe Mamani',
            'email' => 'Rosa.Quispe@example.com',
            'phone' => '982 123 456',
            'department' => 'Madre de Dios',
            'province' => 'Tambopata',
            'district' => 'Las Piedras',
            'address' => 'Jr. Cusco 123',
            'password' => 'Secreta123456',
            'password_confirmation' => 'Secreta123456',
            'privacy' => '1',
            'truthful' => '1',
            'e_notifications' => '1',
            ...$overrides,
        ];
    }

    public function test_the_service_starts_disabled_and_explains_where_to_file_meanwhile(): void
    {
        $this->get('/mesa-de-partes')
            ->assertOk()
            ->assertSee('La Mesa de Partes Virtual todavía no recibe documentos')
            ->assertDontSee('Crear una cuenta');

        $this->get('/mesa-de-partes/registro')->assertRedirect(route('mesa-partes.index'));
    }

    public function test_every_page_of_the_service_renders_once_it_is_switched_on(): void
    {
        $this->configureMesaPartes();

        $this->get('/mesa-de-partes')->assertOk()->assertSee('Crear una cuenta');
        $this->get('/mesa-de-partes/registro')->assertOk()->assertSee(['Persona natural', 'Persona jurídica', 'Leer el aviso de privacidad']);
        $this->get('/mesa-de-partes/ingresar')->assertOk()->assertSee('Ingresar');
        $this->get('/mesa-de-partes/consulta')->assertOk()->assertSee('Consulta de expediente');
        $this->get('/mesa-de-partes/recuperar-contrasena')->assertOk()->assertSee('Recuperar contraseña');
        $this->get('/mesa-de-partes/restablecer-contrasena/token-de-prueba?email=rosa@example.com')->assertOk()->assertSee('Nueva contraseña');

        $this->actingAs(Citizen::factory()->unverified()->create(), 'citizen');
        $this->get('/mesa-de-partes/verificar-correo')->assertOk()->assertSee('Confirme su correo');

        $this->actingAs(Citizen::factory()->create(), 'citizen');
        $this->get('/mesa-de-partes/mi-cuenta/tramites/nuevo')->assertOk()->assertSee('Presentar documento');
        $this->get('/mesa-de-partes/mi-cuenta/notificaciones')->assertOk()->assertSee('Notificaciones');
        $this->get('/mesa-de-partes/mi-cuenta/perfil')->assertOk()->assertSee('Mi perfil');
    }

    public function test_citizens_register_confirm_their_email_and_reach_their_account(): void
    {
        Notification::fake();
        $this->configureMesaPartes();

        $this->post('/mesa-de-partes/registro', $this->registration())
            ->assertRedirect(route('mesa-partes.verification.notice'));

        $citizen = Citizen::query()->sole();
        $this->assertSame('rosa.quispe@example.com', $citizen->email);
        $this->assertNotNull($citizen->privacy_accepted_at);
        $this->assertTrue($citizen->acceptsElectronicNotifications());
        $this->assertAuthenticatedAs($citizen, 'citizen');
        Notification::assertSentTo($citizen, VerifyCitizenEmail::class);

        $this->get('/mesa-de-partes/mi-cuenta')->assertRedirect(route('mesa-partes.verification.notice'));

        $link = URL::temporarySignedRoute('mesa-partes.verification.verify', now()->addHour(), ['id' => $citizen->id, 'hash' => sha1($citizen->email)]);
        $this->get($link)->assertRedirect(route('mesa-partes.account'));

        $this->assertTrue($citizen->fresh()->hasVerifiedEmail());
        $this->get('/mesa-de-partes/mi-cuenta')->assertOk()->assertSee('Aún no presentó documentos');
    }

    public function test_registration_checks_identity_documents_and_consent(): void
    {
        $this->configureMesaPartes();
        Citizen::factory()->create(['document_number' => '45678912']);

        $this->post('/mesa-de-partes/registro', $this->registration(['privacy' => null]))
            ->assertSessionHasErrors(['document_number', 'privacy']);

        $this->post('/mesa-de-partes/registro', $this->registration(['person_type' => 'juridica', 'document_type' => 'dni', 'document_number' => '20123456789']))
            ->assertSessionHasErrors(['document_type', 'business_name', 'representative_name']);

        $this->configureMesaPartes(['mpv_allow_legal_entities' => '0']);
        $this->post('/mesa-de-partes/registro', $this->registration(['person_type' => 'juridica', 'document_type' => 'ruc', 'document_number' => '20123456789']))
            ->assertSessionHasErrors('person_type');

        $this->assertSame(1, Citizen::query()->count());
    }

    public function test_legal_entities_register_with_ruc_and_representative(): void
    {
        Notification::fake();
        $this->configureMesaPartes();

        $this->post('/mesa-de-partes/registro', $this->registration([
            'person_type' => 'juridica', 'document_type' => 'ruc', 'document_number' => '20123456789',
            'first_name' => null, 'last_name' => null,
            'business_name' => 'Cooperativa Agraria Castañera', 'representative_name' => 'Luis Huamán', 'representative_document_number' => '41234567',
            'e_notifications' => null,
        ]))->assertSessionHasNoErrors();

        $citizen = Citizen::query()->sole();
        $this->assertSame('Cooperativa Agraria Castañera', $citizen->fullName());
        $this->assertFalse($citizen->acceptsElectronicNotifications());
    }

    public function test_sign_in_is_limited_and_suspended_accounts_are_signed_out(): void
    {
        $citizen = Citizen::factory()->create(['email' => 'rosa@example.com']);

        $this->post('/mesa-de-partes/ingresar', ['email' => 'rosa@example.com', 'password' => 'otra-clave'])->assertSessionHasErrors('email');
        $this->assertGuest('citizen');

        $this->post('/mesa-de-partes/ingresar', ['email' => 'rosa@example.com', 'password' => 'Secreta123456'])->assertRedirect(route('mesa-partes.account'));
        $this->assertAuthenticatedAs($citizen, 'citizen');
        $this->assertNotNull($citizen->fresh()->last_login_at);

        $citizen->update(['is_active' => false]);
        $this->reloadCitizen();
        $this->get('/mesa-de-partes/mi-cuenta')->assertRedirect(route('mesa-partes.login'));
        $this->assertGuest('citizen');
    }

    public function test_citizen_accounts_never_open_the_admin_panel(): void
    {
        $citizen = Citizen::factory()->create();

        $this->actingAs($citizen, 'citizen')->get('/admin')->assertRedirect('/admin/login');
        $this->get('/mesa-de-partes/mi-cuenta')->assertOk();
    }

    public function test_password_reset_uses_its_own_link_and_never_reveals_accounts(): void
    {
        Notification::fake();
        $citizen = Citizen::factory()->create(['email' => 'rosa@example.com']);

        $this->post('/mesa-de-partes/recuperar-contrasena', ['email' => 'nadie@example.com'])->assertSessionHas('status');
        $this->post('/mesa-de-partes/recuperar-contrasena', ['email' => 'rosa@example.com'])->assertSessionHas('status');

        Notification::assertSentTo($citizen, ResetCitizenPassword::class, function (ResetCitizenPassword $notification) use ($citizen): bool {
            $this->post('/mesa-de-partes/restablecer-contrasena', [
                'token' => $notification->token, 'email' => $citizen->email,
                'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
            ])->assertRedirect(route('mesa-partes.login'));

            return true;
        });

        $this->post('/mesa-de-partes/ingresar', ['email' => 'rosa@example.com', 'password' => 'NuevaClave2026'])->assertRedirect(route('mesa-partes.account'));
    }

    public function test_citizens_update_contact_data_and_can_revoke_electronic_notifications(): void
    {
        $citizen = Citizen::factory()->create();

        $this->actingAs($citizen, 'citizen')->put('/mesa-de-partes/mi-cuenta/perfil', [
            'phone' => '987 654 321', 'department' => 'Cusco', 'province' => 'Cusco', 'district' => 'Wanchaq', 'address' => 'Av. La Cultura 100',
        ])->assertSessionHasNoErrors();

        $citizen->refresh();
        $this->assertSame('Wanchaq', $citizen->district);
        $this->assertFalse($citizen->acceptsElectronicNotifications());
    }
}
