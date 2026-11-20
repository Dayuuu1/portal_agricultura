<?php

namespace Tests\Feature;

use App\Filament\Resources\Slides\Pages\ManageSlides;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
}
