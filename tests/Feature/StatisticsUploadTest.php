<?php

namespace Tests\Feature;

use App\Enums\DocumentSection;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Widgets\PendingTasks;
use App\Filament\Widgets\QuickActions;
use App\Filament\Widgets\StatisticsCalendar;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Support\Documents\TitleCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StatisticsUploadTest extends TestCase
{
    use RefreshDatabase;

    private const ALL = ['view', 'create', 'update', 'delete', 'publish'];

    private DocumentType $crops;

    private DocumentType $prices;

    private DocumentType $resolutions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-20 10:00');

        $this->crops = DocumentType::factory()->section(DocumentSection::InformacionAgraria)->create(['name' => 'Estadística Agrícola', 'group_by_year' => true, 'sort_order' => 1]);
        $this->prices = DocumentType::factory()->section(DocumentSection::InformacionAgraria)->create(['name' => 'Precios de Mercados', 'group_by_year' => true, 'sort_order' => 2]);
        $this->resolutions = DocumentType::factory()->create(['name' => 'Resoluciones Directorales', 'group_by_year' => true]);
    }

    private function statisticsStaff(): User
    {
        return User::factory()->withPermissions(['documentos' => self::ALL], [$this->crops->id, $this->prices->id])->create();
    }

    public function test_the_bulk_upload_lets_staff_review_each_title_and_year_before_saving(): void
    {
        Storage::fake('public');
        $this->actingAs($this->statisticsStaff());

        $component = Livewire::test(ListDocuments::class)
            ->mountAction('bulkUpload')
            ->set('mountedActions.0.data.document_type_id', $this->crops->id)
            ->set('mountedActions.0.data.files', [
                UploadedFile::fake()->create('Produccion agricola diciembre 2025.pdf', 20, 'application/pdf'),
                UploadedFile::fake()->create('Precios_en_chacra_octubre_2026.pdf', 20, 'application/pdf'),
            ]);

        $rows = collect($component->get('mountedActions.0.data.documents'));

        $this->assertSame(
            [['Produccion agricola diciembre 2025', 2025], ['Precios en chacra octubre 2026', 2026]],
            $rows->map(fn (array $row): array => [$row['title'], $row['period_year']])->values()->all(),
        );

        $component
            ->set('mountedActions.0.data.documents.'.$rows->keys()->first().'.title', 'Producción agrícola diciembre 2025')
            ->assertMountedActionModalSee(['Archivo: Produccion agricola diciembre 2025.pdf', 'Diciembre 2025', 'Octubre 2026'])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        $this->assertSame(
            [['Precios en chacra octubre 2026', 2026], ['Producción agrícola diciembre 2025', 2025]],
            Document::query()->orderBy('title')->get()->map(fn (Document $document): array => [$document->title, $document->period_year])->all(),
        );
    }

    public function test_titles_are_checked_for_duplicates_and_for_the_month_statistics_are_filed_under(): void
    {
        Document::factory()->for($this->crops)->create(['title' => 'Producción agrícola julio 2026', 'period_year' => 2026]);

        $this->assertSame('Ya está en el portal', TitleCheck::for('Producción agrícola julio 2026', $this->crops, 2026)->badge());
        $this->assertFalse(TitleCheck::for('Producción agrícola julio 2026', $this->crops, 2025)->isDuplicate);
        $this->assertSame('Agosto 2026', TitleCheck::for('Producción agrícola agosto 2026', $this->crops, 2026)->badge());
        $this->assertSame('Sin mes en el título', TitleCheck::for('Producción agrícola anual', $this->crops, 2026)->badge());
        $this->assertSame('Listo', TitleCheck::for('RD N° 045-2026', $this->resolutions, 2026)->badge());

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateDocument::class)
            ->fillForm(['document_type_id' => $this->crops->id, 'title' => 'Producción agrícola anual', 'period_year' => 2026])
            ->assertSee('No se reconoce el mes en el título');

        Livewire::test(CreateDocument::class)
            ->fillForm(['document_type_id' => $this->crops->id, 'title' => 'Producción agrícola julio 2026', 'period_year' => 2026])
            ->assertSee('Ya hay un documento de este tipo y año con el mismo título');
    }

    public function test_uploading_the_file_first_proposes_the_title_and_year(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateDocument::class)
            ->set('data.file_path', UploadedFile::fake()->create('Precios_de_mercado_mayo_2025.pdf', 20, 'application/pdf'))
            ->assertSchemaStateSet(['title' => 'Precios de mercado mayo 2025', 'period_year' => 2025]);
    }

    public function test_type_lists_follow_the_portal_sections_and_a_single_type_is_preselected(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->assertSame(['Normas y resoluciones', 'Información agraria'], array_keys(DocumentResource::groupedTypeOptions()));

        $this->actingAs($this->statisticsStaff());
        $this->assertSame(['Información agraria' => [$this->crops->id => 'Estadística Agrícola', $this->prices->id => 'Precios de Mercados']], DocumentResource::groupedTypeOptions());

        $this->actingAs(User::factory()->withPermissions(['documentos' => self::ALL], [$this->prices->id])->create());
        Livewire::test(CreateDocument::class)->assertSchemaStateSet(['document_type_id' => $this->prices->id]);
    }

    public function test_statistics_staff_get_a_list_focused_on_their_section_with_the_calendar(): void
    {
        $this->actingAs($this->statisticsStaff());

        $this->get(DocumentResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Su rol le permite subir los documentos de «Información agraria».')
            ->assertSee('Calendario de estadística 2026')
            ->assertDontSee('Gestión institucional');

        $this->actingAs(User::factory()->withPermissions(['documentos' => self::ALL], [$this->prices->id])->create());

        $this->get(DocumentResource::getUrl('index'))->assertOk()->assertSee('Su rol le permite subir Precios de Mercados.');

        $this->actingAs(User::factory()->admin()->create());

        $this->get(DocumentResource::getUrl('index'))->assertOk()->assertDontSee('Calendario de estadística');
        $this->get(DocumentResource::getUrl('index', ['tab' => 'informacion-agraria']))->assertOk()->assertSee('Calendario de estadística 2026');
    }

    public function test_the_calendar_shows_published_hidden_and_missing_months(): void
    {
        $this->actingAs($this->statisticsStaff());

        Document::factory()->for($this->crops)->create(['title' => 'Producción agrícola julio 2026', 'period_year' => 2026]);
        Document::factory()->for($this->crops)->unpublished()->create(['title' => 'Superficie sembrada agosto 2026', 'period_year' => 2026]);
        Document::factory()->for($this->crops)->create(['title' => 'Producción agrícola diciembre 2025', 'period_year' => 2026]);
        Document::factory()->for($this->crops)->create(['title' => 'Campaña agrícola 2025-2026', 'period_year' => 2026]);
        Document::factory()->for($this->prices)->create(['title' => 'Precios en chacra noviembre 2025', 'period_year' => 2025]);

        Livewire::test(StatisticsCalendar::class)
            ->assertSee('Calendario de estadística 2026')
            ->assertSee('Julio: 1 documento publicado')
            ->assertSee('Agosto: 1 documento sin publicar')
            ->assertSee('Marzo: sin documentos. Subir ahora')
            ->assertSee('Noviembre: todavía no corresponde')
            ->assertSee('1 sin mes en el título')
            ->assertSee(DocumentResource::getUrl('index', ['action' => 'bulkUpload', 'actionArguments' => ['tipo' => $this->crops->slug, 'anio' => 2026]]))
            ->call('previousYear')
            ->assertSee('Calendario de estadística 2025')
            ->assertSee('Diciembre: 1 documento publicado');
    }

    public function test_the_calendar_is_only_for_people_who_handle_statistics(): void
    {
        $this->actingAs(User::factory()->withPermissions(['documentos' => self::ALL], [$this->resolutions->id])->create());

        $this->assertFalse(StatisticsCalendar::canView());
    }

    public function test_calendar_links_open_the_bulk_upload_on_the_type_and_year(): void
    {
        $this->actingAs($this->statisticsStaff());

        Livewire::test(ListDocuments::class)
            ->mountAction('bulkUpload', ['tipo' => $this->prices->slug, 'anio' => 2025])
            ->assertSchemaStateSet(['document_type_id' => $this->prices->id, 'period_year' => 2025]);

        Livewire::test(ListDocuments::class)
            ->mountAction('bulkUpload', ['tipo' => $this->resolutions->slug])
            ->assertSchemaStateSet(['document_type_id' => null, 'period_year' => 2026]);
    }

    public function test_dashboard_shortcuts_lead_statistics_staff_to_their_tasks(): void
    {
        $this->actingAs($this->statisticsStaff());
        Document::factory()->for($this->crops)->unpublished()->create();

        Livewire::test(QuickActions::class)
            ->assertSee('Subir estadística del mes')
            ->assertSee(DocumentResource::getUrl('index', ['action' => 'bulkUpload']));

        Livewire::test(PendingTasks::class)
            ->assertSee(DocumentResource::getUrl('index', ['tab' => 'sin-publicar']));
    }
}
