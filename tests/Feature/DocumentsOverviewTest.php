<?php

namespace Tests\Feature;

use App\Enums\DocumentSection;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentsOverviewTest extends TestCase
{
    use RefreshDatabase;

    private DocumentType $resolutions;

    private DocumentType $livestock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolutions = DocumentType::factory()->section(DocumentSection::Normativa)->create(['name' => 'Resoluciones Directorales']);
        $this->livestock = DocumentType::factory()->section(DocumentSection::InformacionAgraria)->create(['name' => 'Estadística Pecuaria']);

        Document::factory()->for($this->resolutions)->create(['title' => 'Resolución de saneamiento', 'period_year' => 2024, 'issued_at' => '2024-03-01']);
        Document::factory()->for($this->resolutions)->create(['title' => 'Resolución de nombramiento', 'period_year' => 2026, 'issued_at' => '2026-10-05']);
        Document::factory()->for($this->resolutions)->unpublished()->create(['title' => 'Resolución en borrador', 'period_year' => 2027]);
        Document::factory()->for($this->livestock)->create(['title' => 'Beneficio de ganado', 'period_year' => 2025, 'issued_at' => '2025-09-11']);
    }

    public function test_documents_open_on_a_card_per_type_grouped_by_section(): void
    {
        $empty = DocumentType::factory()->section(DocumentSection::Gestion)->create(['name' => 'Control Institucional']);

        $this->get('/documentos')
            ->assertOk()
            ->assertSeeInOrder([
                'Normas y resoluciones', route('documents.index', ['tipo' => $this->resolutions->slug]), '2 documentos', 'de 2024 a 2026', 'Actualizado el', '5 oct. 2026',
            ], false)
            ->assertSeeInOrder(['Publicados recientemente', 'Resolución de nombramiento', 'Resolución de saneamiento'])
            ->assertSee('Ver los 3 documentos en lista')
            ->assertDontSee('Resolución en borrador')
            ->assertDontSee(route('documents.index', ['tipo' => $empty->slug]), false);
    }

    public function test_agrarian_statistics_only_get_a_card_leading_to_their_own_section(): void
    {
        $response = $this->get('/documentos')
            ->assertOk()
            ->assertSeeInOrder(['Información agraria', '1 reporte; el último se publicó el 11 sep. 2025', 'Ir a Información agraria'])
            ->assertSee('href="'.route('agrarian-information').'"', false)
            ->assertDontSee('Beneficio de ganado');

        // The main menu still lists the agrarian topics; the page body leads only to their section.
        $this->assertStringNotContainsString($this->livestock->publicUrl(), Str::between($response->getContent(), '<main', '</main>'));
    }

    public function test_a_section_shows_only_its_types_and_latest_documents(): void
    {
        $this->get('/documentos?seccion=normativa')
            ->assertOk()
            ->assertSee(route('documents.index', ['tipo' => $this->resolutions->slug]), false)
            ->assertSee('Resolución de nombramiento')
            ->assertDontSee('Beneficio de ganado')
            ->assertSee(route('documents.index', ['seccion' => 'normativa', 'vista' => 'lista']));
    }

    public function test_the_complete_list_and_searches_keep_the_plain_list(): void
    {
        $this->get('/documentos?vista=lista')
            ->assertOk()
            ->assertSee('Todos los documentos')
            ->assertSee('3 documentos')
            ->assertSee('Resolución de saneamiento')
            ->assertSee('Beneficio de ganado')
            ->assertDontSee('Publicados recientemente');

        $this->get('/documentos?q=ganado')
            ->assertOk()
            ->assertSee('Beneficio de ganado')
            ->assertDontSee('Resolución de saneamiento');
    }

    public function test_the_main_menu_links_documents_to_its_overview(): void
    {
        $this->get('/')
            ->assertSee('href="'.route('documents.index').'"', false)
            ->assertSee('Más opciones de Documentos');
    }
}
