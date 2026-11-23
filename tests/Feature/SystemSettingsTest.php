<?php

namespace Tests\Feature;

use App\Filament\Pages\SystemSettings;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Support\OutgoingMail;
use App\Support\Permissions;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Once;
use Livewire\Livewire;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function server(array $overrides = []): array
    {
        return [
            'mail_enabled' => true,
            'mail_host' => 'smtp.dramdd.gob.pe',
            'mail_encryption' => 'tls',
            'mail_port' => '587',
            'mail_username' => 'mesadepartes@dramdd.gob.pe',
            'mail_password' => 'ClaveSmtp2026',
            'mail_from_address' => 'mesadepartes@dramdd.gob.pe',
            'mail_from_name' => 'Mesa de Partes DRA',
            'panel_mfa_required' => 'none',
            ...$overrides,
        ];
    }

    public function test_only_administrators_open_the_mail_and_access_page(): void
    {
        $this->actingAs(User::factory()->withPermissions(Permissions::all())->create())
            ->get(SystemSettings::getUrl())
            ->assertForbidden();

        $this->flushSession();
        $this->actingAs(User::factory()->admin()->create())
            ->get(SystemSettings::getUrl())
            ->assertOk()
            ->assertSee(['Correo saliente', 'Los correos no se están enviando', 'Verificación en dos pasos']);
    }

    public function test_the_smtp_password_is_stored_encrypted_and_never_shown_again(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SystemSettings::class)
            ->fillForm($this->server())
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertSet('data.mail_password', null)
            ->assertDontSee('ClaveSmtp2026');

        $stored = Setting::query()->where('key', 'mail_password')->value('value');
        $this->assertNotSame('ClaveSmtp2026', $stored);
        $this->assertSame('ClaveSmtp2026', Crypt::decryptString($stored));

        // Saving with the field empty keeps the password; the history only says it changed.
        Livewire::test(SystemSettings::class)->fillForm(['mail_port' => '2525'])->call('save')->assertHasNoFormErrors();
        $this->assertSame($stored, Setting::query()->where('key', 'mail_password')->value('value'));

        $logged = ActivityLog::query()->where('event', 'updated')->get()->pluck('changes')->toJson();
        $this->assertStringContainsString('(dato protegido)', $logged);
        $this->assertStringNotContainsString($stored, $logged);
    }

    public function test_enabling_the_server_requires_its_address_and_sender(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SystemSettings::class)
            ->fillForm($this->server(['mail_host' => '', 'mail_from_address' => '']))
            ->call('save')
            ->assertHasFormErrors(['mail_host' => 'required', 'mail_from_address' => 'required']);

        Livewire::test(SystemSettings::class)
            ->fillForm($this->server(['mail_host' => 'smtp://smtp.dramdd.gob.pe']))
            ->call('save')
            ->assertHasFormErrors(['mail_host']);
    }

    public function test_the_portal_sends_mail_through_the_stored_server_once_enabled(): void
    {
        foreach ($this->server(['mail_enabled' => '1', 'mail_password' => OutgoingMail::encryptPassword('ClaveSmtp2026')]) as $key => $value) {
            Setting::query()->where('key', $key)->sole()->update(['value' => $value]);
        }

        Once::flush();
        $this->app->forgetInstance('mail.manager');
        Mail::clearResolvedInstances();

        $transport = $this->app->make('mail.manager')->mailer()->getSymfonyTransport();

        $this->assertSame(OutgoingMail::MAILER, config('mail.default'));
        $this->assertSame('mesadepartes@dramdd.gob.pe', config('mail.from.address'));
        $this->assertSame('Mesa de Partes DRA', config('mail.from.name'));
        $this->assertSame('ClaveSmtp2026', config('mail.mailers.portal.password'));
        $this->assertInstanceOf(EsmtpTransport::class, $transport);
        $this->assertSame('smtp.dramdd.gob.pe', $transport->getStream()->getHost());
        $this->assertSame(587, $transport->getStream()->getPort());
    }

    public function test_the_test_message_explains_why_it_could_not_be_sent(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SystemSettings::class)
            ->fillForm($this->server(['mail_host' => '', 'mail_enabled' => false]))
            ->callAction('sendTest', data: ['to' => 'ti@dramdd.gob.pe'])
            ->assertNotified('Faltan datos para probar');

        // Nothing listens on this port, so the connection is refused and the reason is shown.
        Livewire::test(SystemSettings::class)
            ->fillForm($this->server(['mail_host' => '127.0.0.1', 'mail_port' => '1', 'mail_encryption' => 'none', 'mail_enabled' => false]))
            ->callAction('sendTest', data: ['to' => 'ti@dramdd.gob.pe'])
            ->assertNotified('No se pudo enviar el correo de prueba');
    }

    public function test_portal_data_never_shows_the_mail_settings(): void
    {
        $this->seed(SettingSeeder::class);
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/datos-del-portal')
            ->assertOk()
            ->assertDontSee(['Contraseña SMTP', 'Servidor SMTP', 'mail_password']);
    }
}
