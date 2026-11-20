<?php

namespace Tests\Feature;

use App\Enums\DocumentSection;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private DocumentType $agricultural;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agricultural = DocumentType::factory()->section(DocumentSection::InformacionAgraria)->create([
            'name' => 'Estadística Agrícola',
            'group_by_year' => true,
        ]);
    }

    private function publish(string $title, string $issuedAt): Document
    {
        return Document::factory()->for($this->agricultural)->create(['title' => $title, 'issued_at' => $issuedAt]);
    }

    private function yearPage(int $year, array $query = []): string
    {
        return route('documents.index', ['tipo' => $this->agricultural->slug, 'anio' => $year, ...$query]);
    }

    public function test_a_statistics_year_is_organized_by_the_month_each_report_covers(): void
    {
        $this->publish('Producción de los principales cultivos – junio 2025', '2025-07-21');
        $this->publish('Producción de los principales cultivos – julio 2025', '2025-09-11');
        $this->publish('Precio en chacra de los principales cultivos – julio 2025', '2025-09-11');

        $this->get($this->yearPage(2025))
            ->assertOk()
            ->assertSeeInOrder([
                'Julio 2025', '11 de septiembre de 2025', 'Producción de los principales cultivos', 'Precio en chacra de los principales cultivos',
                'Junio 2025', 'Producción de los principales cultivos',
            ])
            ->assertDontSee('Producción de los principales cultivos – julio 2025');
    }

    public function test_reports_whose_titles_have_no_month_are_grouped_by_publication_day(): void
    {
        $this->publish('Producción de los principales cultivos (t.) año 2019', '2019-02-27');
        $this->publish('Rendimiento de los principales cultivos kgha año 2019', '2019-02-27');
        $this->publish('Producción de los principales cultivos (t.) año 2019', '2019-03-26');

        $this->get($this->yearPage(2019))
            ->assertOk()
            ->assertSeeInOrder(['Publicado el 26 de marzo de 2019', 'Publicado el 27 de febrero de 2019', 'Rendimiento de los principales cultivos kgha año 2019']);
    }

    public function test_a_report_without_month_joins_the_monthly_release_published_the_same_day(): void
    {
        $this->publish('Producción de los principales cultivos – octubre 2026', '2026-11-19');
        $this->publish('Siembra de los principales cultivos, agosto – octubre / 2026 – 2027', '2026-11-19');

        $this->get($this->yearPage(2026))
            ->assertOk()
            ->assertSeeInOrder(['Octubre 2026', 'Producción de los principales cultivos', 'Siembra de los principales cultivos, agosto – octubre / 2026 – 2027'])
            ->assertDontSee('Publicado el 19 de noviembre de 2026</h3>', false);
    }

    public function test_the_indicator_view_lays_out_each_indicator_on_a_monthly_calendar(): void
    {
        $production = $this->publish('Producción de los principales cultivos – enero 2025', '2025-03-02');
        $this->publish('Producción de los principales cultivos – febrero 2025', '2025-03-26');
        $this->publish('Precio en chacra de los principales cultivos – enero 2025', '2025-03-02');
        $this->publish('Precio en chacra de los principales cultivos – febrero 2025', '2025-03-26');

        $this->get($this->yearPage(2025))->assertSee('Por indicador');

        $this->get($this->yearPage(2025, ['vista' => 'indicador']))
            ->assertOk()
            ->assertSeeInOrder(['Producción de los principales cultivos', '2 publicaciones', 'Precio en chacra de los principales cultivos'])
            ->assertSee(route('documents.show', $production))
            ->assertSee('Marzo: sin publicación')
            ->assertDontSee('Febrero 2025');
    }

    public function test_the_indicator_view_is_only_offered_when_several_indicators_repeat(): void
    {
        $this->publish('Información agroindustrial de molinos de arroz – junio 2025', '2025-07-21');
        $this->publish('Información agroindustrial de molinos de arroz – julio 2025', '2025-09-11');

        $this->get($this->yearPage(2025, ['vista' => 'indicador']))
            ->assertOk()
            ->assertDontSee('Por indicador')
            ->assertSeeInOrder(['Julio 2025', 'Junio 2025']);
    }

    public function test_searches_all_years_and_other_sections_keep_the_plain_list(): void
    {
        $report = $this->publish('Precio en chacra de los principales cultivos – julio 2025', '2025-09-11');
        $resolutions = DocumentType::factory()->section(DocumentSection::Normativa)->create(['group_by_year' => true]);
        $resolution = Document::factory()->for($resolutions)->create(['title' => 'Resolución Directoral Regional N° 075-2025', 'issued_at' => '2025-05-02']);

        $this->get(route('documents.index', ['tipo' => $this->agricultural->slug, 'q' => 'precio']))
            ->assertSee($report->title)
            ->assertDontSee('Julio 2025');

        $this->get(route('documents.index', ['tipo' => $this->agricultural->slug, 'anio' => 'todos']))
            ->assertSee($report->title);

        $this->get(route('documents.index', ['tipo' => $resolutions->slug]))
            ->assertSee($resolution->title)
            ->assertDontSee('Publicado el');
    }
}
