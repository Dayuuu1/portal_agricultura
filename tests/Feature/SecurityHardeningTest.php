<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Resources\Agencies\Pages\EditAgency;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Filament\Resources\Settings\Pages\EditSettings;
use App\Filament\Resources\Slides\Pages\ManageSlides;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\Citizen;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\Slide;
use App\Models\User;
use App\Notifications\MesaPartes\ResetCitizenPassword;
use App\Rules\SafeLink;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SettingSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Once;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_admin_pages_send_security_headers(): void
    {
        foreach (['/', '/admin/login'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeaderMissing('Strict-Transport-Security');
        }
    }

    public function test_new_accounts_need_a_strong_password(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $create = fn (string $password) => Livewire::test(ManageUsers::class)->callAction('create', data: [
            'name' => 'Rosa Quispe',
            'email' => 'rosa@dramdd.gob.pe',
            'password' => $password,
            'is_active' => true,
            'is_admin' => true,
        ]);

        $create('123')->assertHasActionErrors(['password']);
        $create('solominusculas')->assertHasActionErrors(['password']);
        $create('Agricultura2026')->assertHasNoActionErrors();
    }

    public function test_svg_files_cannot_be_uploaded_as_images(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSlides::class)
            ->callAction('create', data: [
                'title' => 'Banner',
                'image' => UploadedFile::fake()->create('banner.svg', 2, 'image/svg+xml'),
            ])
            ->assertHasActionErrors(['image']);
    }

    public function test_two_factor_secrets_are_encrypted_and_never_serialized(): void
    {
        $user = User::factory()->admin()->create();
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

        $stored = DB::table('users')->where('id', $user->id)->value('app_authentication_secret');

        $this->assertNotSame('JBSWY3DPEHPK3PXP', $stored);
        $this->assertSame('JBSWY3DPEHPK3PXP', $user->fresh()->getAppAuthenticationSecret());
        $this->assertArrayNotHasKey('app_authentication_secret', $user->fresh()->toArray());
    }

    public function test_pages_send_a_content_security_policy(): void
    {
        $policy = (string) $this->get('/')->assertOk()->headers->get('Content-Security-Policy');

        foreach (["base-uri 'self'", "form-action 'self'", "frame-ancestors 'self'", "object-src 'self'"] as $directive) {
            $this->assertStringContainsString($directive, $policy);
        }
    }

    public function test_emailed_links_use_the_application_url_whatever_host_the_request_names(): void
    {
        Notification::fake();
        $citizen = Citizen::factory()->create(['email' => 'rosa@example.com']);

        $this->post('http://sitio-falso.test/mesa-de-partes/recuperar-contrasena', ['email' => 'rosa@example.com'])->assertRedirect();

        Notification::assertSentTo($citizen, ResetCitizenPassword::class, function (ResetCitizenPassword $notification) use ($citizen): bool {
            $url = (string) $notification->toMail($citizen)->actionUrl;

            return str_starts_with($url, rtrim((string) config('app.url'), '/').'/') && ! str_contains($url, 'sitio-falso');
        });
    }

    public function test_rich_text_keeps_only_the_editor_classes_and_harmless_styles(): void
    {
        $html = (string) str('<p class="fixed inset-0 z-50 lead" style="position: fixed; z-index: 99; text-align: center">Aviso</p>'
            .'<span style="color: #b91c1c; background-image: url(https://otro-sitio.test/a.png)">Texto</span>')->sanitizeHtml();

        $this->assertStringContainsString('class="lead"', $html);
        $this->assertStringContainsString('text-align: center', $html);
        $this->assertStringContainsString('color: #b91c1c', $html);

        foreach (['fixed', 'z-50', 'position', 'z-index', 'url('] as $removed) {
            $this->assertStringNotContainsString($removed, $html);
        }
    }

    public function test_links_edited_in_the_panel_cannot_run_code(): void
    {
        $fails = fn (string $link): bool => Validator::make(['link' => $link], ['link' => [new SafeLink]])->fails();

        $this->assertTrue($fails('javascript:alert(1)'));
        $this->assertTrue($fails('//otro-sitio.test'));
        $this->assertTrue($fails('data:text/html,hola'));
        $this->assertFalse($fails('/convocatorias'));
        $this->assertFalse($fails('https://www.gob.pe/midagri'));

        // A value saved before the check existed is never printed.
        Slide::factory()->create(['button_url' => 'javascript:alert(1)']);
        $this->get('/')->assertOk()->assertDontSee('javascript:alert(1)', false);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(ManageRedirects::class)
            ->callAction('create', data: ['old_path' => '/pagina-antigua', 'new_path' => 'javascript:alert(1)'])
            ->assertHasActionErrors(['new_path']);
    }

    public function test_switches_in_tables_follow_the_edit_permission(): void
    {
        $slide = Slide::factory()->create(['is_active' => true]);

        $this->actingAs(User::factory()->withPermissions(['banners' => ['view']])->create());
        Livewire::test(ManageSlides::class)->call('updateTableColumnState', 'is_active', (string) $slide->getKey(), false);
        $this->assertTrue($slide->fresh()->is_active);

        $this->actingAs(User::factory()->withPermissions(['banners' => ['view', 'update']])->create());
        Livewire::test(ManageSlides::class)->call('updateTableColumnState', 'is_active', (string) $slide->getKey(), false);
        $this->assertFalse($slide->fresh()->is_active);
    }

    public function test_marking_messages_as_read_needs_the_edit_permission(): void
    {
        $message = ContactMessage::factory()->create();

        $this->actingAs(User::factory()->withPermissions(['mensajes' => ['view']])->create());
        Livewire::test(ListContactMessages::class)->assertActionHidden(TestAction::make('markAsRead')->table($message));

        $this->actingAs(User::factory()->withPermissions(['mensajes' => ['view', 'update']])->create());
        Livewire::test(ListContactMessages::class)->callAction(TestAction::make('markAsRead')->table($message));
        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_a_stored_file_cannot_be_swapped_for_another_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('documentos/resolucion.pdf', '%PDF-1.4');
        $this->seed(SettingSeeder::class);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditSettings::class)
            ->set('data.site_logo', ['tampered' => 'documentos/resolucion.pdf'])
            ->call('save')
            ->assertHasErrors('data.site_logo');

        // Every other upload of the panel gets the same check by default.
        $agency = Agency::factory()->create(['photo' => null]);
        Livewire::test(EditAgency::class, ['record' => $agency->getRouteKey()])
            ->set('data.photo', ['tampered' => 'documentos/resolucion.pdf'])
            ->call('save')
            ->assertHasErrors('data.photo');
        $this->assertNull($agency->fresh()->photo);

        // Even a value changed behind the form's back only ever removes files of the logo folder.
        DB::table('settings')->where('key', 'site_logo')->update(['value' => 'documentos/resolucion.pdf']);
        Setting::query()->where('key', 'site_logo')->sole()->update(['value' => null]);
        Storage::disk('public')->assertExists('documentos/resolucion.pdf');
    }

    public function test_accounts_with_history_are_deactivated_instead_of_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $withHistory = User::factory()->create();
        ActivityLog::record('login', $withHistory, userId: $withHistory->id);
        $neverUsed = User::factory()->create();

        $this->assertFalse($admin->can('delete', $withHistory));
        $this->assertTrue($admin->can('delete', $neverUsed));
    }

    public function test_the_staff_password_reset_does_not_reveal_who_has_an_account(): void
    {
        Notification::fake();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'nadie@dramdd.gob.pe'])
            ->call('request')
            ->assertNotified(__(Password::RESET_LINK_SENT));

        Notification::assertNothingSent();
    }

    public function test_the_two_step_policy_decides_who_must_set_it_up(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->withPermissions(['noticias' => ['view']])->create();

        $this->actingAs($admin)->get('/admin')->assertOk();

        Setting::query()->where('key', 'panel_mfa_required')->sole()->update(['value' => 'admins']);
        Once::flush();

        $this->actingAs($admin)->get('/admin')->assertRedirectContains('multi-factor-authentication/set-up');

        // Another person, another browser: the panel session is bound to the password of whoever signed in.
        $this->flushSession();
        $this->actingAs($editor)->get('/admin')->assertOk();
    }

    public function test_seeding_again_never_resets_the_administrator_password(): void
    {
        User::factory()->admin()->create(['email' => 'admin@dramdd.gob.pe', 'password' => 'Original2026x']);

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check('Original2026x', User::query()->where('email', 'admin@dramdd.gob.pe')->sole()->password));
    }
}
