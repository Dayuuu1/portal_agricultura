<?php

namespace Tests\Feature;

use App\Enums\DeviceType;
use App\Enums\SiteEventType;
use App\Enums\SiteSection;
use App\Enums\TrafficSource;
use App\Filament\Widgets\Analytics\SearchesWithoutResults;
use App\Filament\Widgets\Analytics\SiteOverview;
use App\Models\Document;
use App\Models\PageView;
use App\Models\SiteEvent;
use App\Models\User;
use App\Support\Analytics\AnalyticsPeriod;
use App\Support\Analytics\SiteStatistics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SiteAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = 'Mozilla/5.0 (Linux; Android 14; SM-A145M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36';

    /**
     * @param  array<string, mixed>  $data
     */
    private function visit(array $data, string $userAgent = self::PHONE): TestResponse
    {
        return $this->withHeader('User-Agent', $userAgent)->postJson(route('analytics.store'), [
            'title' => 'Noticias | Dirección Regional Agraria',
            ...$data,
        ]);
    }

    public function test_a_page_view_is_recorded_without_personal_data(): void
    {
        $this->visit(['path' => '/noticias', 'referrer' => 'https://www.google.com/', 'width' => 412])
            ->assertOk()
            ->assertJsonStructure(['url']);

        $view = PageView::query()->sole();

        $this->assertSame('/noticias', $view->path);
        $this->assertSame('Noticias', $view->title);
        $this->assertSame(SiteSection::News, $view->section);
        $this->assertSame(TrafficSource::SearchEngine, $view->source);
        $this->assertSame('google.com', $view->referrer_host);
        $this->assertSame([DeviceType::Mobile, 'Chrome', 'Android'], [$view->device, $view->browser, $view->os]);
        $this->assertTrue($view->is_entry);
        $this->assertSame(16, strlen($view->visitor));
        $this->assertStringNotContainsString('127.0.0.1', json_encode($view->getAttributes()));
    }

    public function test_pages_opened_within_half_an_hour_belong_to_the_same_visit(): void
    {
        $this->visit(['path' => '/']);
        $this->travel(10)->minutes();
        $this->visit(['path' => '/documentos', 'referrer' => 'https://www.facebook.com/']);
        $this->travel(45)->minutes();
        $this->visit(['path' => '/convocatorias']);

        [$first, $second, $third] = PageView::query()->orderBy('id')->get()->all();

        $this->assertSame($first->session, $second->session);
        $this->assertFalse($second->is_entry);
        $this->assertNull($second->source, 'Only the first page of a visit says where it came from.');
        $this->assertNotSame($second->session, $third->session);
        $this->assertTrue($third->is_entry);
        $this->assertSame($first->visitor, $third->visitor);
    }

    #[TestWith(['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])]
    #[TestWith(['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/131.0.0.0 Safari/537.36'])]
    #[TestWith(['facebookexternalhit/1.1'])]
    #[TestWith([''])]
    public function test_robots_are_not_counted(string $userAgent): void
    {
        $this->visit(['path' => '/'], $userAgent)->assertNoContent();

        $this->assertSame(0, PageView::query()->count());
    }

    public function test_panel_staff_are_not_counted_nor_tracked(): void
    {
        $this->get('/')->assertSee('name="analytics-endpoint"', false);

        $this->actingAs(User::factory()->admin()->create());

        $this->get('/')->assertDontSee('name="analytics-endpoint"', false);
        $this->visit(['path' => '/'])->assertNoContent();
        $this->assertSame(0, PageView::query()->count());
    }

    #[TestWith(['/mesa-de-partes/restablecer-contrasena/9f8e7d6c5b4a', '/mesa-de-partes/restablecer-contrasena'])]
    #[TestWith(['/mesa-de-partes/mi-cuenta/tramites/123', '/mesa-de-partes/mi-cuenta'])]
    #[TestWith(['/libro-de-reclamaciones/constancia/RC-2026-0007', '/libro-de-reclamaciones/constancia'])]
    public function test_addresses_that_may_identify_a_person_are_kept_only_as_their_section(string $visited, string $stored): void
    {
        $this->visit(['path' => $visited, 'title' => 'Constancia de reclamo RC-2026-0007 | Portal']);

        $view = PageView::query()->sole();

        $this->assertSame($stored, $view->path);
        $this->assertNull($view->title);
    }

    public function test_query_strings_are_dropped_and_internal_referrers_count_as_direct(): void
    {
        $this->visit(['path' => '/documentos?q=dni&token=secret', 'referrer' => 'http://localhost/noticias']);

        $view = PageView::query()->sole();

        $this->assertSame('/documentos', $view->path);
        $this->assertSame(TrafficSource::Direct, $view->source);
        $this->assertNull($view->referrer_host);
    }

    public function test_searches_are_recorded_with_their_number_of_results(): void
    {
        $this->visit(['path' => '/buscar', 'search_term' => '  Resolución   CAS ', 'search_results' => 0, 'search_scope' => 'portal']);

        $search = SiteEvent::query()->sole();

        $this->assertSame(SiteEventType::Search, $search->type);
        $this->assertSame('resolución cas', $search->label);
        $this->assertSame(0, $search->value);
        $this->assertSame('portal', $search->context);
    }

    public function test_search_pages_tell_the_statistics_what_was_searched(): void
    {
        $this->get('/buscar?q=fiebre+aftosa')
            ->assertSee('data-analytics-search', false)
            ->assertSee('data-term="fiebre aftosa"', false)
            ->assertSee('data-results="0"', false);

        $this->get('/documentos?q=tupa')->assertSee('data-scope="documentos"', false);
        $this->get('/noticias?q=feria')->assertSee('data-scope="noticias"', false);
        $this->get('/esta-direccion-no-existe')->assertSee('data-analytics-not-found', false);
    }

    public function test_time_downloads_and_external_links_are_reported_on_a_signed_address(): void
    {
        $url = $this->visit(['path' => '/documentos/tupa-2026'])->json('url');
        $view = PageView::query()->sole();

        $this->post($url, ['action' => 'leave', 'seconds' => 95])->assertNoContent();
        $this->post($url, ['action' => 'leave', 'seconds' => 40])->assertNoContent();
        $this->assertSame(95, $view->refresh()->duration, 'The longest report wins.');

        $this->post($url, ['action' => 'leave', 'seconds' => 7200]);
        $this->assertSame(PageView::MAX_DURATION, $view->refresh()->duration);

        $this->post($url, ['action' => 'download', 'target' => '/documentos/tupa-2026/descargar'])->assertNoContent();
        $this->post($url, ['action' => 'outbound', 'target' => 'https://www.gob.pe/midagri?utm=x'])->assertNoContent();
        $this->post($url, ['action' => 'download', 'target' => '/otra/cosa'])->assertNoContent();

        $this->assertSame(
            [['download', 'tupa-2026'], ['outbound', 'gob.pe']],
            SiteEvent::query()->orderBy('id')->get()->map(fn (SiteEvent $event): array => [$event->type->value, $event->label])->all(),
        );
    }

    public function test_reports_without_a_valid_signature_are_refused(): void
    {
        $view = PageView::factory()->create(['duration' => 10]);

        $this->post(route('analytics.update', $view), ['action' => 'leave', 'seconds' => 999])->assertForbidden();

        $tampered = URL::temporarySignedRoute('analytics.update', now()->addHour(), ['pageView' => $view], absolute: false);
        $other = PageView::factory()->create();
        $this->post(str_replace('/'.$view->id.'?', '/'.$other->id.'?', $tampered), ['action' => 'leave', 'seconds' => 999])->assertForbidden();

        $this->assertSame(10, $view->refresh()->duration);
    }

    public function test_the_summary_works_out_visits_bounce_rate_and_duration(): void
    {
        // Visit 1: three pages (60 + 30 + 30 s). Visit 2: one page and out (20 s).
        $entry = PageView::factory()->create(['duration' => 60]);
        PageView::factory()->inVisit($entry, '/noticias')->create(['duration' => 30]);
        PageView::factory()->inVisit($entry, '/documentos', 5)->create(['duration' => 30]);
        PageView::factory()->create(['duration' => 20]);
        PageView::factory()->on(now()->subDays(40)->format('Y-m-d'))->create();

        $summary = SiteStatistics::summary(AnalyticsPeriod::lastDays(30));

        $this->assertSame(2, $summary['visitors']);
        $this->assertSame(2, $summary['visits']);
        $this->assertSame(4, $summary['views']);
        $this->assertSame(50.0, $summary['bounceRate']);
        $this->assertSame(70, $summary['visitDuration']);
        $this->assertSame(2.0, $summary['pagesPerVisit']);
        $this->assertSame('1 min 10 s', SiteStatistics::formatDuration($summary['visitDuration']));
    }

    public function test_downloads_are_reported_with_the_document_title(): void
    {
        $document = Document::factory()->create(['slug' => 'tupa-2026', 'title' => 'TUPA 2026']);
        SiteEvent::factory()->count(3)->create(['type' => SiteEventType::Download, 'label' => $document->slug]);
        SiteEvent::factory()->create(['type' => SiteEventType::Download, 'label' => 'documento-retirado']);

        $downloads = SiteStatistics::downloads(AnalyticsPeriod::lastDays(7));

        $this->assertSame(['TUPA 2026', 3], [$downloads[0]['title'], $downloads[0]['downloads']]);
        $this->assertSame('documento-retirado', $downloads[1]['title']);
    }

    #[TestWith([['period' => 'custom', 'start' => 'nonsense', 'end' => '2026-01-01'], 30])]
    #[TestWith([['period' => 'custom', 'start' => '2026-11-20', 'end' => '2026-11-10'], 30])]
    #[TestWith([['period' => "7'; drop table page_views; --"], 30])]
    #[TestWith([['period' => '7'], 7])]
    #[TestWith([null, 30])]
    public function test_unexpected_filter_values_fall_back_to_the_last_30_days(?array $filters, int $days): void
    {
        $period = AnalyticsPeriod::fromFilters($filters);

        $this->assertSame($days, $period->days());
        $this->assertTrue($period->end->isToday());
        $this->assertSame($days, $period->previous()->days());
    }

    public function test_the_statistics_page_requires_its_permission(): void
    {
        $this->actingAs(User::factory()->withPermissions(['noticias' => ['view']])->create())
            ->get('/admin/estadisticas')
            ->assertForbidden();

        $this->actingAs(User::factory()->withPermissions(['estadisticas' => ['view']])->create())
            ->get('/admin/estadisticas')
            ->assertOk()
            ->assertSee('Estadísticas del portal')
            ->assertSee('Periodo');
    }

    public function test_the_widgets_show_the_figures_of_the_chosen_period(): void
    {
        $this->actingAs(User::factory()->withPermissions(['estadisticas' => ['view']])->create());
        $entry = PageView::factory()->create();
        PageView::factory()->inVisit($entry)->create();
        SiteEvent::factory()->count(2)->search('certificado de posesión', 0)->create();

        Livewire::withoutLazyLoading()
            ->test(SiteOverview::class, ['pageFilters' => ['period' => '7']])
            ->assertSee('Visitantes')
            ->assertSee('Tasa de rebote')
            ->assertSee('Sin datos del periodo anterior para comparar');

        Livewire::withoutLazyLoading()
            ->test(SearchesWithoutResults::class, ['pageFilters' => ['period' => '7']])
            ->assertSee('certificado de posesión')
            ->assertSee('buscador general');
    }

    public function test_statistics_older_than_the_retention_period_are_deleted(): void
    {
        $old = PageView::factory()->create(['created_at' => now()->subMonths(PageView::RETENTION_MONTHS + 1)]);
        $recent = PageView::factory()->create();

        $this->artisan('model:prune', ['--model' => [PageView::class, SiteEvent::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }
}
