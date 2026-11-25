<?php

namespace Tests\Feature;

use App\Enums\SiteEventType;
use App\Models\PageView;
use App\Models\SiteEvent;
use App\Models\User;
use App\Support\Analytics\AnalyticsPeriod;
use App\Support\Analytics\StatisticsReport;
use Database\Seeders\SettingSeeder;
use DateInterval;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StatisticsReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
    }

    private function analyst(): User
    {
        return User::factory()->withPermissions(['estadisticas' => ['view']])->create(['name' => 'Analista de Imagen']);
    }

    private function seedVisits(): void
    {
        $entry = PageView::factory()->create(['path' => '/noticias/feria', 'title' => 'Feria del cacao', 'duration' => 120]);
        PageView::factory()->inVisit($entry, '/documentos/tupa-2026')->create(['title' => 'TUPA 2026', 'duration' => 60]);
        PageView::factory()->create(['path' => '/noticias/feria', 'title' => 'Feria del cacao', 'duration' => 30]);
        SiteEvent::factory()->count(3)->search('certificado de posesión', 0)->create();
        SiteEvent::factory()->create(['type' => SiteEventType::NotFound, 'label' => '/wp-content/boletin.pdf']);
    }

    /**
     * @return array<string, list<list<mixed>>> Sheet name => rows.
     */
    private function readWorkbook(string $path): array
    {
        $options = new Options;
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true;
        $reader = new Reader($options);
        $reader->open($path);
        $sheets = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $sheets[$sheet->getName()][] = $row->toArray();
            }
        }

        $reader->close();

        return $sheets;
    }

    public function test_the_page_is_organised_in_tabs_and_offers_the_reports(): void
    {
        $this->actingAs($this->analyst())
            ->get('/admin/estadisticas')
            ->assertOk()
            ->assertSeeInOrder(['Visitas y audiencia', 'Contenido', 'Búsquedas', 'Documentos', 'Enlaces y errores'])
            ->assertSee('Exportar informe')
            ->assertSee('Excel (.xlsx)')
            ->assertSee('Informe para imprimir o PDF')
            ->assertSee(route('admin.statistics.export', ['period' => '30']), false);
    }

    public function test_the_excel_workbook_has_a_summary_and_one_sheet_per_report(): void
    {
        $this->seedVisits();

        $response = $this->actingAs($this->analyst())->get(route('admin.statistics.export', ['period' => '7']));

        $response->assertOk()->assertDownload('estadisticas-portal-'.today()->subDays(6)->format('Y-m-d').'-al-'.today()->format('Y-m-d').'.xlsx');

        $sheets = $this->readWorkbook($response->getFile()->getPathname());

        $this->assertSame(
            ['Resumen', 'Por día', 'Páginas', 'Secciones', 'Origen del tráfico', 'Sitios de referencia', 'Dispositivos', 'Navegadores', 'Sistemas operativos', 'Horas', 'Búsquedas', 'Búsquedas sin resultados', 'Documentos consultados', 'Descargas', 'Enlaces externos', 'Direcciones no encontradas'],
            array_keys($sheets),
        );

        $this->assertSame('Estadísticas del portal · Resumen', $sheets['Resumen'][0][0]);
        $this->assertStringContainsString('Analista de Imagen', $sheets['Resumen'][3][0]);
        $this->assertSame(['Indicador', 'Periodo analizado', 'Periodo anterior', 'Variación'], $sheets['Resumen'][5]);
        $this->assertSame(['Visitantes', '2', '0', ''], $sheets['Resumen'][6], 'No change is shown when the previous period had nothing to compare with.');

        $pages = array_slice($sheets['Páginas'], 6);
        $this->assertSame(['Feria del cacao', '/noticias/feria', 2, 2], array_slice($pages[0], 0, 4));
        $this->assertInstanceOf(DateInterval::class, $pages[0][4], 'Times are real Excel durations (minutes:seconds).');
        $this->assertSame(75, $pages[0][4]->h * 3600 + $pages[0][4]->i * 60 + $pages[0][4]->s);

        $this->assertInstanceOf(DateTimeInterface::class, $sheets['Por día'][6][0], 'Dates are real Excel dates.');
        $this->assertSame(['certificado de posesión', 3, 'Buscador general'], $sheets['Búsquedas sin resultados'][6]);
        $this->assertSame(['/wp-content/boletin.pdf', 1], $sheets['Direcciones no encontradas'][6]);
        $this->assertSame(['Sin datos en el periodo elegido.'], $sheets['Descargas'][6]);
    }

    public function test_the_printable_report_shows_the_period_and_every_section(): void
    {
        $this->seedVisits();

        $this->actingAs($this->analyst())
            ->get(route('admin.statistics.report', ['period' => '7']))
            ->assertOk()
            ->assertSee('Informe de estadísticas del portal web')
            ->assertSee(AnalyticsPeriod::lastDays(7)->label())
            ->assertSee('Analista de Imagen')
            ->assertSeeInOrder(['1. Indicadores clave', '2. Evolución de las visitas', '5. Páginas más vistas', '9. Búsquedas sin resultados', '13. Direcciones que no existen'])
            ->assertSee('Feria del cacao')
            ->assertSee('certificado de posesión')
            ->assertSee('/wp-content/boletin.pdf')
            ->assertSee('Imprimir o guardar PDF');
    }

    public function test_reports_require_the_statistics_permission(): void
    {
        $this->get(route('admin.statistics.export'))->assertRedirect(route('filament.admin.auth.login'));

        $this->actingAs(User::factory()->withPermissions(['noticias' => ['view']])->create());

        $this->get(route('admin.statistics.export'))->assertForbidden();
        $this->get(route('admin.statistics.report'))->assertForbidden();
    }

    #[TestWith(['7', 'día', 7])]
    #[TestWith(['90', 'semana', 14])]
    #[TestWith(['365', 'mes', 13])]
    #[TestWith(['today', 'hora', 24])]
    public function test_the_printed_trend_groups_long_periods_so_it_fits_on_the_page(string $period, string $unit, int $maxBuckets): void
    {
        $trend = (new StatisticsReport(AnalyticsPeriod::fromFilters(['period' => $period])))->trendBuckets();

        $this->assertSame($unit, $trend['unit']);
        $this->assertLessThanOrEqual($maxBuckets, count($trend['buckets']));
        $this->assertGreaterThan(0, count($trend['buckets']));
    }
}
