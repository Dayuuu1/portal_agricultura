<?php

namespace Tests\Feature;

use App\Enums\DocumentSection;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgrarianInformationTest extends TestCase
{
    use RefreshDatabase;

    private DocumentType $agricultural;

    private DocumentType $bulletins;

    private DocumentType $marketPrices;

    private DocumentType $resolutions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-24 10:00');

        $agrarian = DocumentType::factory()->section(DocumentSection::InformacionAgraria);
        $this->agricultural = $agrarian->create(['name' => 'Estadística Agrícola', 'group_by_year' => true, 'sort_order' => 1]);
        $this->bulletins = $agrarian->create(['name' => 'Boletines Estadísticos', 'group_by_year' => true, 'sort_order' => 2]);
        $this->marketPrices = $agrarian->create(['name' => 'Precios de Mercados', 'group_by_year' => true, 'sort_order' => 3]);
        $this->resolutions = DocumentType::factory()->section(DocumentSection::Normativa)->create(['name' => 'Resoluciones Directorales']);

        $this->publish($this->agricultural, 'Producción de los principales cultivos – agosto 2025', '2025-09-20');
        $this->publish($this->agricultural, 'Precio en chacra de los principales cultivos – setiembre 2026', '2026-11-19');
        $this->publish($this->agricultural, 'Producción de los principales cultivos – octubre 2026', '2026-11-19');
        $this->publish($this->agricultural, 'Precio en chacra de los principales cultivos – octubre 2026', '2026-11-19');
        $this->publish($this->bulletins, 'Boletín octubre 2026', '2026-11-19');
        $this->publish($this->marketPrices, 'Precios al minorista de productos agrícola y pecuario fecha 26 de mayo', '2020-05-26');
        $this->publish($this->resolutions, 'Resolución Directoral Regional N° 075-2026', '2026-11-20');
    }

    private function publish(DocumentType $type, string $title, string $issuedAt): Document
    {
        return Document::factory()->for($type)->create(['title' => $title, 'issued_at' => $issuedAt]);
    }

    public function test_each_topic_shows_the_last_month_it_reports_and_its_years(): void
    {
        $this->get(route('agrarian-information'))
            ->assertOk()
            ->assertSeeText('6 reportes desde 2020')
            ->assertSeeText('Última actualización: 19 de noviembre de 2026')
            ->assertSeeInOrder([
                'Temas',
                'Estadística Agrícola', 'Último reporte:', 'octubre 2026',
                $this->agricultural->publicUrl(['anio' => 2026]), $this->agricultural->publicUrl(['anio' => 2025]),
                'Boletines Estadísticos',
            ], false)
            ->assertDontSee('Resolución Directoral Regional N° 075-2026');
    }

    public function test_the_latest_publication_day_lists_its_reports_by_topic_newest_month_first(): void
    {
        $this->get(route('agrarian-information'))
            ->assertSeeTextInOrder([
                'Lo último publicado', 'Reportes publicados el 19 de noviembre de 2026',
                'Estadística Agrícola',
                'Producción de los principales cultivos – octubre 2026',
                'Precio en chacra de los principales cultivos – octubre 2026',
                'Precio en chacra de los principales cultivos – setiembre 2026',
                'Boletines Estadísticos', 'Boletín octubre 2026',
            ])
            ->assertDontSee('Producción de los principales cultivos – agosto 2025');
    }

    public function test_topics_without_recent_publications_are_shown_apart(): void
    {
        $this->get(route('agrarian-information'))
            ->assertSeeInOrder(['Lo último publicado', 'Series sin publicaciones recientes', 'Precios de Mercados', 'Último reporte:', 'mayo 2020']);
    }

    public function test_former_documents_addresses_lead_to_agrarian_information(): void
    {
        $this->get('/documentos?tipo='.$this->agricultural->slug.'&anio=2025')
            ->assertStatus(301)
            ->assertRedirect($this->agricultural->publicUrl(['anio' => 2025]));

        $this->get('/documentos?seccion=informacion-agraria')
            ->assertStatus(301)
            ->assertRedirect(route('agrarian-information'));

        $this->get(route('agrarian-information.type', $this->resolutions))
            ->assertStatus(301)
            ->assertRedirect($this->resolutions->publicUrl());
    }

    public function test_a_topic_page_stays_within_agrarian_information(): void
    {
        $this->get($this->agricultural->publicUrl())
            ->assertOk()
            ->assertSeeInOrder(['Información agraria', 'Estadística Agrícola'])
            ->assertSee('Resumen de información agraria')
            ->assertSee('Precios de Mercados')
            ->assertDontSee('Resoluciones Directorales')
            ->assertSee('action="'.$this->agricultural->publicUrl().'"', false)
            ->assertSee($this->agricultural->publicUrl(['anio' => 2025]), false);
    }

    public function test_searching_reports_keeps_agrarian_information_around(): void
    {
        $this->get(route('documents.index', ['seccion' => 'informacion-agraria', 'vista' => 'lista', 'q' => 'precio']))
            ->assertOk()
            ->assertSee('Reportes de información agraria')
            ->assertSee('Precio en chacra de los principales cultivos – octubre 2026')
            ->assertDontSee('Boletín octubre 2026')
            ->assertDontSee('Resoluciones Directorales');
    }

    public function test_a_report_page_belongs_to_agrarian_information(): void
    {
        $report = Document::query()->where('title', 'Boletín octubre 2026')->firstOrFail();

        $this->get(route('documents.show', $report))
            ->assertOk()
            ->assertSeeInOrder(['Información agraria', 'Boletines Estadísticos', 'Detalle'])
            ->assertSee('href="'.$this->bulletins->publicUrl().'"', false);
    }

    public function test_the_home_page_keeps_reports_out_of_the_recent_documents(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();
        $recentDocuments = Str::between($html, 'Documentos recientes', 'Boletines estadísticos y precios de mercado');

        $this->assertStringContainsString('Resolución Directoral Regional N° 075-2026', $recentDocuments);
        $this->assertStringNotContainsString('Boletín octubre 2026', $recentDocuments);
    }
}
