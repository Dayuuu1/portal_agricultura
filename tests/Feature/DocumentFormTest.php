<?php

namespace Tests\Feature;

use App\Enums\DocumentSection;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentFormTest extends TestCase
{
    use RefreshDatabase;

    private DocumentType $crops;

    private DocumentType $resolutions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crops = DocumentType::factory()->section(DocumentSection::InformacionAgraria)->create(['name' => 'Estadística Agrícola', 'group_by_year' => true]);
        $this->resolutions = DocumentType::factory()->create(['name' => 'Resoluciones Directorales', 'group_by_year' => true]);

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_a_document_cannot_be_published_without_a_file_or_a_link(): void
    {
        $data = ['document_type_id' => $this->resolutions->id, 'title' => 'Resolución Directoral N° 045-2026', 'period_year' => 2026];

        Livewire::test(CreateDocument::class)
            ->fillForm([...$data, 'is_published' => true])
            ->call('create')
            ->assertHasFormErrors(['file_path' => 'required']);

        Livewire::test(CreateDocument::class)
            ->fillForm([...$data, 'is_published' => true, 'external_url' => 'https://www.gob.pe/institucion/regionmadrededios/normas-legales/1'])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(CreateDocument::class)
            ->fillForm([...$data, 'title' => 'Resolución en preparación', 'is_published' => false])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(2, Document::query()->count());
    }

    public function test_choosing_a_type_proposes_the_unit_that_usually_issues_it(): void
    {
        $statisticsOffice = OrganizationalUnit::factory()->create();
        Document::factory()->for($this->crops)->count(2)->create(['organizational_unit_id' => $statisticsOffice->id]);

        Livewire::test(CreateDocument::class)
            ->set('data.document_type_id', $this->crops->id)
            ->assertSchemaStateSet(['organizational_unit_id' => $statisticsOffice->id]);

        Livewire::test(ListDocuments::class)
            ->mountAction('bulkUpload', ['tipo' => $this->crops->slug])
            ->assertSchemaStateSet(['document_type_id' => $this->crops->id, 'organizational_unit_id' => $statisticsOffice->id]);
    }

    public function test_statistics_skip_the_document_number_and_the_summary_shows_where_it_will_appear(): void
    {
        Livewire::test(CreateDocument::class)
            ->fillForm(['document_type_id' => $this->crops->id, 'title' => 'Producción agrícola julio 2026', 'period_year' => 2026, 'is_published' => true])
            ->assertFormFieldHidden('code')
            ->assertSee('Dónde aparecerá en el portal')
            ->assertSee(['Información agraria', 'Estadística Agrícola', 'Entre las publicaciones de julio 2026.', 'Visible para el público al guardar.']);

        Livewire::test(CreateDocument::class)
            ->fillForm(['document_type_id' => $this->resolutions->id, 'is_published' => false])
            ->assertFormFieldVisible('code')
            ->assertSee('Se guardará oculto, sin publicar.')
            ->assertDontSee('Entre las publicaciones de');
    }
}
