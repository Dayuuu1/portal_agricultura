<?php

namespace Tests\Feature;

use App\Filament\Resources\Settings\Pages\EditSettings;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
    }

    public function test_every_value_is_edited_in_one_form_organised_by_topic(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/datos-del-portal')
            ->assertOk()
            ->assertSee(['Identidad', 'Misión y visión', 'Contacto', 'Redes sociales', 'Servicios en línea'])
            ->assertSee(['Vista previa', 'Logo e ícono', 'Guardar cambios'])
            ->assertSee('Textos referenciales')
            ->assertSee('Datos sin completar: no se muestran en el portal');

        Livewire::test(EditSettings::class)
            ->assertSchemaStateSet([
                'site_name' => 'Dirección Regional de Desarrollo Agropecuario y Riego de Madre de Dios',
                'phone_main' => '082 572668',
            ]);
    }

    public function test_saving_updates_only_the_values_that_changed(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $logsBefore = ActivityLog::query()->count();

        Livewire::test(EditSettings::class)
            ->fillForm([
                'mission' => 'Misión oficial aprobada por la entidad.',
                'mesa_partes_url' => 'https://facilita.gob.pe/t/1234',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Cambios guardados');

        $this->assertSame('Misión oficial aprobada por la entidad.', Setting::valueOf('mission'));
        $this->assertSame('https://facilita.gob.pe/t/1234', Setting::query()->where('key', 'mesa_partes_url')->value('value'));
        $this->assertSame(2, ActivityLog::query()->count() - $logsBefore);

        Livewire::test(EditSettings::class)
            ->call('save')
            ->assertNotified('No había cambios por guardar');
    }

    public function test_the_entity_name_is_required_and_links_must_be_addresses(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditSettings::class)
            ->fillForm(['site_name' => '', 'facebook_url' => 'facebook agricultura'])
            ->call('save')
            ->assertHasFormErrors(['site_name' => 'required', 'facebook_url' => 'url']);

        $this->assertSame('Dirección Regional de Desarrollo Agropecuario y Riego de Madre de Dios', Setting::query()->where('key', 'site_name')->value('value'));
    }

    public function test_users_who_can_only_view_get_a_read_only_page(): void
    {
        $this->actingAs(User::factory()->withPermissions(['datos_portal' => ['view']])->create());

        $this->get('/admin/datos-del-portal')->assertOk()->assertDontSee('Guardar cambios');

        Livewire::test(EditSettings::class)
            ->assertFormFieldDisabled('site_name')
            ->call('save')
            ->assertForbidden();
    }
}
