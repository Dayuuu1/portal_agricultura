<?php

namespace Tests\Feature;

use App\Enums\UnitType;
use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use App\Filament\Resources\OrganizationalUnits\Pages\EditOrganizationalUnit;
use App\Filament\Resources\OrganizationalUnits\Pages\ListOrganizationalUnits;
use App\Models\Agency;
use App\Models\Complaint;
use App\Models\Document;
use App\Models\JobPosting;
use App\Models\OrganizationalUnit;
use App\Models\ProcedureType;
use App\Models\Setting;
use App\Models\Submission;
use App\Models\User;
use App\Support\OrganizationalUnits\OrganizationChart;
use App\Support\OrganizationalUnits\UnitFunctions;
use Closure;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrganizationalUnitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_lists_each_body_followed_by_the_units_under_it(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $direction = OrganizationalUnit::factory()->create(['name' => 'Dirección Regional', 'sort_order' => 0]);
        $planning = OrganizationalUnit::factory()->create(['name' => 'Oficina de Planeamiento', 'parent_id' => $direction->id, 'sort_order' => 2]);
        $administration = OrganizationalUnit::factory()->create(['name' => 'Oficina de Administración', 'parent_id' => $direction->id, 'sort_order' => 1]);
        $treasury = OrganizationalUnit::factory()->create(['name' => 'Unidad de Tesorería', 'parent_id' => $administration->id, 'sort_order' => 2]);
        $accounting = OrganizationalUnit::factory()->create(['name' => 'Unidad de Contabilidad', 'parent_id' => $administration->id, 'sort_order' => 1]);
        $budget = OrganizationalUnit::factory()->create(['name' => 'Unidad de Presupuesto', 'parent_id' => $planning->id, 'sort_order' => 1]);

        Livewire::test(ListOrganizationalUnits::class)
            ->assertCanSeeTableRecords([$direction, $administration, $accounting, $treasury, $planning, $budget], inOrder: true);
    }

    public function test_incomplete_tab_lists_units_whose_public_page_lacks_data(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $complete = OrganizationalUnit::factory()->create(['head_photo' => 'unidades/jefe.jpg', 'email' => 'oaj@example.test']);
        $withoutContact = OrganizationalUnit::factory()->create(['head_photo' => 'unidades/jefa.jpg']);
        $withoutHead = OrganizationalUnit::factory()->create(['head_name' => null, 'head_photo' => 'unidades/foto.jpg', 'phone' => '082 000000']);

        Livewire::withQueryParams(['tab' => 'ficha-incompleta'])
            ->test(ListOrganizationalUnits::class)
            ->assertCanSeeTableRecords([$withoutContact, $withoutHead])
            ->assertCanNotSeeTableRecords([$complete]);

        $this->assertSame(['correo o teléfono'], $withoutContact->missingProfileItems());
        $this->assertSame(['responsable'], $withoutHead->missingProfileItems());
    }

    /**
     * @return array<string, array{Closure(OrganizationalUnit): mixed}>
     */
    public static function linkedRecords(): array
    {
        return [
            'a unit under it' => [fn (OrganizationalUnit $unit) => OrganizationalUnit::factory()->create(['parent_id' => $unit->id])],
            'a document' => [fn (OrganizationalUnit $unit) => Document::factory()->create(['organizational_unit_id' => $unit->id])],
            'a job posting' => [fn (OrganizationalUnit $unit) => JobPosting::factory()->create(['organizational_unit_id' => $unit->id])],
            'a complaint' => [fn (OrganizationalUnit $unit) => Complaint::factory()->create(['organizational_unit_id' => $unit->id])],
            'a procedure type' => [fn (OrganizationalUnit $unit) => ProcedureType::factory()->create(['default_unit_id' => $unit->id])],
            'a submission addressed to it' => [fn (OrganizationalUnit $unit) => Submission::factory()->create(['target_unit_id' => $unit->id])],
            'a submission it holds' => [fn (OrganizationalUnit $unit) => Submission::factory()->create(['current_unit_id' => $unit->id])],
            'a submission that went through it' => [fn (OrganizationalUnit $unit) => Submission::factory()->create()->movements()->create([
                'action' => 'derivado', 'to_status' => 'derivado', 'unit_id' => $unit->id,
            ])],
        ];
    }

    #[DataProvider('linkedRecords')]
    public function test_a_unit_other_records_point_to_cannot_be_deleted(Closure $link): void
    {
        $admin = User::factory()->admin()->create();
        $unit = OrganizationalUnit::factory()->create();

        $this->assertTrue($admin->can('delete', $unit));

        $link($unit);

        $this->assertFalse($admin->can('delete', $unit->fresh()));
    }

    public function test_delete_button_explains_why_a_unit_in_use_stays(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $unit = OrganizationalUnit::factory()->create();
        Document::factory()->count(2)->create(['organizational_unit_id' => $unit->id]);
        OrganizationalUnit::factory()->create(['parent_id' => $unit->id]);

        Livewire::test(ListOrganizationalUnits::class)->assertActionDisabled(TestAction::make('delete')->table($unit));

        $this->assertSame(
            'Está vinculada a 1 unidad que depende de ella y 2 documentos. Para retirarla del portal, desactive «Visible en el portal».',
            OrganizationalUnitResource::deletionBlockedMessage($unit),
        );
        $this->assertModelExists($unit);
    }

    public function test_a_unit_nothing_points_to_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $unit = OrganizationalUnit::factory()->create();

        Livewire::test(ListOrganizationalUnits::class)->callAction(TestAction::make('delete')->table($unit));

        $this->assertModelMissing($unit);
    }

    public function test_depends_on_only_accepts_bodies_where_the_unit_fits_in_the_chart(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $direction = OrganizationalUnit::factory()->create();
        $office = OrganizationalUnit::factory()->create(['parent_id' => $direction->id]);
        $unit = OrganizationalUnit::factory()->create(['parent_id' => $office->id]);
        $otherOffice = OrganizationalUnit::factory()->create(['parent_id' => $direction->id]);

        // Under its own unit it would depend on itself; under another office its units would fall off the chart.
        foreach ([$unit, $otherOffice] as $invalidParent) {
            Livewire::test(EditOrganizationalUnit::class, ['record' => $office->getRouteKey()])
                ->fillForm(['parent_id' => $invalidParent->id])
                ->call('save')
                ->assertHasFormErrors(['parent_id']);
        }

        Livewire::test(EditOrganizationalUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm(['parent_id' => $otherOffice->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($direction->id, $office->fresh()->parent_id);
        $this->assertSame($otherOffice->id, $unit->fresh()->parent_id);
    }

    public function test_a_replaced_or_deleted_photo_is_removed_from_storage(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        $unit = OrganizationalUnit::factory()->create(['head_photo' => 'unidades/anterior.jpg']);
        Storage::disk('public')->put('unidades/anterior.jpg', 'foto');

        // Like the upload box in the browser: the current photo is removed before the new one is added.
        Livewire::test(EditOrganizationalUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm(['head_photo' => []])
            ->fillForm(['head_photo' => UploadedFile::fake()->image('nueva.jpg', 600, 600)])
            ->call('save')
            ->assertHasNoFormErrors();

        $newPhoto = $unit->fresh()->head_photo;
        $this->assertStringStartsWith('unidades/', $newPhoto);
        Storage::disk('public')->assertMissing('unidades/anterior.jpg');
        Storage::disk('public')->assertExists($newPhoto);

        $unit->fresh()->delete();

        Storage::disk('public')->assertMissing($newPhoto);
    }

    public function test_photo_cleanup_never_leaves_the_photo_folder(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logos/escudo.png', 'logo');
        $unit = OrganizationalUnit::factory()->create(['head_photo' => 'logos/escudo.png']);

        $unit->update(['head_photo' => null]);

        Storage::disk('public')->assertExists('logos/escudo.png');
    }

    public function test_unit_page_presents_its_head_with_photo_and_direct_contact(): void
    {
        $unit = OrganizationalUnit::factory()->create([
            'name' => 'Oficina de Asesoría Jurídica',
            'head_name' => 'Abog. Rosa Quispe Mamani',
            'head_title' => 'Jefa de Oficina',
            'head_photo' => 'unidades/rosa.jpg',
            'email' => 'oaj@example.test',
            'phone' => '082 123456 anexo 12',
        ]);

        $this->get(route('institutional.unit', $unit))
            ->assertOk()
            ->assertSeeInOrder(['Jefa de Oficina', 'Abog. Rosa Quispe Mamani'])
            ->assertSee('/storage/unidades/rosa.jpg', false)
            ->assertSee('href="tel:082123456"', false)
            ->assertSee('mailto:oaj@example.test', false)
            ->assertDontSee('Por actualizar');
    }

    public function test_unit_page_without_head_or_contact_points_to_the_central_line(): void
    {
        Setting::factory()->create(['key' => 'phone_main', 'value' => '082 572668']);
        $unit = OrganizationalUnit::factory()->create(['head_name' => null, 'email' => null, 'phone' => null]);

        $this->get(route('institutional.unit', $unit))
            ->assertOk()
            ->assertSee('Esta unidad atiende a través de la central de la entidad.')
            ->assertSee('082 572668')
            ->assertDontSee('Por actualizar')
            ->assertDontSee('Jefe(a) de Oficina');
    }

    public function test_head_without_photo_shows_initials_without_the_title(): void
    {
        $unit = OrganizationalUnit::factory()->create(['head_name' => 'Ing. Percy Rojas Villalobos', 'head_photo' => null]);

        $this->get(route('institutional.unit', $unit))->assertSee('>PR<', false);
    }

    public function test_directory_shows_photos_and_marks_posts_without_a_head(): void
    {
        $direction = OrganizationalUnit::factory()->create(['head_name' => 'Luis Rojas', 'head_title' => 'Director Regional', 'head_photo' => 'unidades/luis.jpg']);
        OrganizationalUnit::factory()->create(['parent_id' => $direction->id, 'head_name' => null, 'head_title' => 'Jefe(a) de Oficina']);

        $this->get(route('institutional.directory'))
            ->assertOk()
            ->assertSee('/storage/unidades/luis.jpg', false)
            ->assertSeeInOrder(['Sin designar', 'Jefe(a) de Oficina'])
            ->assertDontSee('Por actualizar');
    }

    public function test_about_page_introduces_the_director_only_once_designated(): void
    {
        $direction = OrganizationalUnit::factory()->create(['head_name' => null, 'head_title' => 'Director(a) Regional']);

        $this->get(route('institutional.about'))->assertOk()->assertDontSee('Por designar')->assertDontSee('Director(a) Regional');

        $direction->update(['head_name' => 'Luis Rojas']);

        $this->get(route('institutional.about'))->assertSeeInOrder(['Director(a) Regional', 'Luis Rojas']);
    }

    public function test_organization_chart_places_each_body_where_its_type_belongs(): void
    {
        $direction = OrganizationalUnit::factory()->create(['type' => UnitType::AltaDireccion]);
        $planning = OrganizationalUnit::factory()->create(['parent_id' => $direction->id, 'type' => UnitType::Asesoramiento, 'sort_order' => 1]);
        $control = OrganizationalUnit::factory()->create(['parent_id' => $direction->id, 'type' => UnitType::Control, 'sort_order' => 5]);
        $legal = OrganizationalUnit::factory()->create(['parent_id' => $direction->id, 'type' => UnitType::Asesoramiento, 'sort_order' => 2]);
        $administration = OrganizationalUnit::factory()->create(['parent_id' => $direction->id, 'type' => UnitType::Apoyo, 'sort_order' => 3]);
        $competitiveness = OrganizationalUnit::factory()->create(['parent_id' => $direction->id, 'type' => UnitType::Linea, 'sort_order' => 4]);
        $agency = Agency::factory()->create();

        $chart = OrganizationChart::for($direction->load(['children.children']), collect([$agency]));

        $this->assertSame(
            [[$control->id, null], [$planning->id, $administration->id], [$legal->id, null]],
            array_map(fn (array $row): array => [$row['left']?->id, $row['right']?->id], $chart->staffRows),
        );
        $this->assertSame([$competitiveness->id], $chart->bottomRow->pluck('id')->all());
        $this->assertSame([$control->id, $planning->id, $legal->id, $administration->id, $competitiveness->id], $chart->bodies()->pluck('id')->all());
        $this->assertSame(
            [UnitType::AltaDireccion, UnitType::Control, UnitType::Asesoramiento, UnitType::Apoyo, UnitType::Linea, UnitType::Desconcentrado],
            $chart->typesShown(),
        );
    }

    public function test_organization_chart_page_draws_the_visible_structure_and_the_agencies(): void
    {
        $direction = OrganizationalUnit::factory()->create(['name' => 'Dirección Regional', 'type' => UnitType::AltaDireccion]);
        $office = OrganizationalUnit::factory()->create(['name' => 'Oficina de Administración', 'acronym' => 'OA', 'parent_id' => $direction->id, 'type' => UnitType::Apoyo]);
        OrganizationalUnit::factory()->create(['name' => 'Unidad de Tesorería', 'parent_id' => $office->id, 'type' => UnitType::Unidad]);
        OrganizationalUnit::factory()->create(['name' => 'Unidad Oculta', 'parent_id' => $office->id, 'type' => UnitType::Unidad, 'is_active' => false]);
        $agency = Agency::factory()->create(['name' => 'Agencia Agraria Tambopata']);

        $this->get(route('institutional.organization-chart'))
            ->assertOk()
            ->assertSeeInOrder(['Dirección Regional', 'Oficina de Administración', 'Unidad de Tesorería', 'Agencia Agraria Tambopata'])
            ->assertSee(route('agencies.show', $agency), false)
            ->assertSee('Órgano Desconcentrado')
            ->assertSee('Imprimir o guardar en PDF')
            ->assertSee('Actualizado el')
            ->assertDontSee('Unidad Oculta');
    }

    public function test_organization_chart_page_without_units_says_it_is_being_prepared(): void
    {
        $this->get(route('institutional.organization-chart'))->assertOk()->assertSee('Organigrama en preparación');
    }

    public function test_functions_written_one_paragraph_each_are_shown_as_a_list(): void
    {
        $html = "<p><strong>Funciones generales</strong></p>\n<p>\u{00A0}Asesorar a la Dirección.</p>\n<p> Emitir informes legales.</p>";

        $this->assertSame(
            '<p><strong>Funciones generales</strong></p><ul><li>Asesorar a la Dirección.</li><li>Emitir informes legales.</li></ul>',
            UnitFunctions::toHtml($html),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function functionsShownAsWritten(): array
    {
        return [
            'already a list' => ['<ul><li>Asesorar.</li><li>Informar.</li></ul>'],
            'lettered items' => ['<p>a) Asesorar.</p><p>b) Informar.</p>'],
            'items with dashes' => ['<p>- Asesorar.</p><p>- Informar.</p>'],
            'a single paragraph' => ['<p>Asesora a la Dirección en asuntos legales.</p>'],
        ];
    }

    #[DataProvider('functionsShownAsWritten')]
    public function test_functions_that_already_mark_their_items_are_shown_as_written(string $html): void
    {
        $this->assertSame($html, UnitFunctions::toHtml($html));
    }

    public function test_functions_are_sanitized_before_being_shown(): void
    {
        $unit = OrganizationalUnit::factory()->create(['functions' => '<p>Asesorar.</p><p>Informar.</p><script>alert("xss")</script>']);

        $this->get(route('institutional.unit', $unit))
            ->assertOk()
            ->assertSee('<li>Asesorar.</li>', false)
            ->assertDontSee('alert("xss")', false);
    }
}
