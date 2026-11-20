<?php

namespace Tests\Feature;

use App\Filament\Resources\Settings\Pages\EditSettings;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_identity_settings_exist_and_start_with_the_bundled_logo(): void
    {
        $this->assertSame(
            ['site_short_name' => 'text', 'site_logo' => 'image', 'site_favicon' => 'image'],
            Setting::query()->whereIn('key', ['site_short_name', 'site_logo', 'site_favicon'])->orderBy('id')->pluck('type', 'key')->all(),
        );

        $this->get('/')
            ->assertOk()
            ->assertSee('Dirección Regional Agraria')
            ->assertSee('images/logo-emblema.png', false)
            ->assertSee('favicon-32.png', false);
    }

    public function test_the_portal_and_the_panel_use_the_name_and_images_set_in_the_admin(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('portal/logo-drdar.png', 'png');
        Storage::disk('public')->put('portal/icono-drdar.png', 'png');

        foreach ([
            'site_short_name' => 'Dirección Regional de Desarrollo Agropecuario y Riego',
            'parent_entity' => 'Gobierno Regional de Madre de Dios',
            'site_logo' => 'portal/logo-drdar.png',
            'site_favicon' => 'portal/icono-drdar.png',
        ] as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['label' => $key, 'value' => $value]);
        }

        $logo = Storage::disk('public')->url('portal/logo-drdar.png');
        $icon = Storage::disk('public')->url('portal/icono-drdar.png');

        $this->get('/')
            ->assertOk()
            ->assertSee('Dirección Regional de Desarrollo Agropecuario y Riego')
            ->assertSee($logo, false)
            ->assertSee('<link rel="icon" href="'.$icon.'">', false)
            ->assertDontSee('images/logo-emblema.png', false);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Dirección Regional de Desarrollo Agropecuario y Riego')
            ->assertSee($logo, false)
            ->assertSee($icon, false);
    }

    public function test_administrators_upload_a_new_logo_and_the_replaced_file_is_removed(): void
    {
        Storage::fake('public');
        $this->seed(SettingSeeder::class);
        $this->actingAs(User::factory()->admin()->create());
        $logo = Setting::query()->where('key', 'site_logo')->sole();
        // Like the upload box in the browser: the current image is removed before the new one is added.
        $upload = fn (UploadedFile $file) => Livewire::test(EditSettings::class)
            ->fillForm(['site_logo' => []])
            ->fillForm(['site_logo' => $file])
            ->call('save');

        $upload(UploadedFile::fake()->image('logo.png', 400, 120))->assertHasNoFormErrors();
        $first = $logo->fresh()->value;

        $upload(UploadedFile::fake()->image('logo-nuevo.png', 400, 120))->assertHasNoFormErrors();
        $second = $logo->fresh()->value;

        $this->assertStringStartsWith('portal/', $second);
        Storage::disk('public')->assertExists($second);
        Storage::disk('public')->assertMissing($first);

        $upload(UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml'))->assertHasFormErrors(['site_logo']);
        $this->assertSame($second, $logo->fresh()->value);
    }
}
