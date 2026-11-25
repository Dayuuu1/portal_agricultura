<?php

namespace Tests\Feature;

use App\Enums\JobPostingStatus;
use App\Filament\Resources\Settings\Pages\EditSettings;
use App\Models\Document;
use App\Models\JobPosting;
use App\Models\OrganizationalUnit;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SearchEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
    }

    /**
     * Outside tests the trusted-hosts middleware stores its patterns in a static property of Symfony's Request,
     * which would outlive this test and reject the other hosts later tests use.
     */
    private function inProduction(): void
    {
        $this->app['env'] = 'production';
        $this->beforeApplicationDestroyed(fn () => Request::setTrustedHosts([]));
    }

    /**
     * @return list<array<string, mixed>> Every schema.org node printed on the page.
     */
    private function structuredData(TestResponse $response): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', (string) $response->getContent(), $blocks);

        return collect($blocks[1])->flatMap(fn (string $json): array => json_decode($json, true, flags: JSON_THROW_ON_ERROR)['@graph'])->all();
    }

    /**
     * @return list<string>
     */
    private function types(TestResponse $response): array
    {
        return array_column($this->structuredData($response), '@type');
    }

    public function test_a_test_or_training_copy_asks_search_engines_to_stay_out(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee("User-agent: *\nDisallow: /", false);

        $this->get('/')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_the_production_site_opens_itself_to_search_engines_and_points_to_its_sitemap(): void
    {
        $this->inProduction();

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Allow: /')
            ->assertSee('Disallow: /admin/')
            ->assertSee('Disallow: /buscar')
            ->assertSee('Sitemap: '.route('seo.sitemap'))
            ->assertDontSee("Disallow: /\n", false);

        $this->get('/')->assertSee('<meta name="robots" content="index, follow, max-image-preview:large">', false);
        $this->get('/buscar?q=tupa')->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_the_sitemap_index_points_to_one_sitemap_per_kind_of_content(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string((string) $response->getContent());

        $this->assertSame(
            ['paginas', 'noticias', 'documentos', 'convocatorias'],
            array_map(fn ($sitemap): string => basename((string) $sitemap->loc, '.xml'), iterator_to_array($xml->sitemap, false)),
        );
    }

    public function test_sitemaps_list_only_published_content(): void
    {
        $post = Post::factory()->create(['cover_image' => 'noticias/feria.jpg']);
        $draft = Post::factory()->draft()->create();
        $document = Document::factory()->create();
        $hiddenDocument = Document::factory()->create(['is_published' => false]);
        $jobPosting = JobPosting::factory()->create();
        $hiddenPosting = JobPosting::factory()->create(['is_published' => false]);
        $unit = OrganizationalUnit::factory()->create();
        $inactiveUnit = OrganizationalUnit::factory()->create(['is_active' => false]);

        $this->get('/sitemaps/noticias.xml')
            ->assertOk()
            ->assertSee(route('posts.show', $post))
            ->assertSee('<image:loc>'.$post->cover_url.'</image:loc>', false)
            ->assertDontSee(route('posts.show', $draft));

        $this->get('/sitemaps/documentos.xml')->assertSee(route('documents.show', $document))->assertDontSee(route('documents.show', $hiddenDocument));
        $this->get('/sitemaps/convocatorias.xml')->assertSee(route('job-postings.show', $jobPosting))->assertDontSee(route('job-postings.show', $hiddenPosting));

        $this->get('/sitemaps/paginas.xml')
            ->assertSee('<loc>'.route('home').'</loc>', false)
            ->assertSee(route('institutional.unit', $unit))
            ->assertDontSee(route('institutional.unit', $inactiveUnit));

        $this->get('/sitemaps/otra-cosa.xml')->assertNotFound();
    }

    public function test_a_new_publication_reaches_the_sitemap_without_waiting_for_the_cache(): void
    {
        $this->get('/sitemaps/noticias.xml');

        $post = Post::factory()->create();

        $this->get('/sitemaps/noticias.xml')->assertSee(route('posts.show', $post));
    }

    #[TestWith(['AbCdEf0123456789-_xyz', 'AbCdEf0123456789-_xyz'])]
    #[TestWith(['<meta name="google-site-verification" content="AbCdEf0123456789-_xyz" />', 'AbCdEf0123456789-_xyz'])]
    public function test_the_google_ownership_code_is_published_on_every_page(string $pasted, string $code): void
    {
        Setting::query()->where('key', 'google_site_verification')->update(['value' => $pasted]);
        Setting::query()->where('key', 'bing_site_verification')->update(['value' => '1A2B3C4D5E6F7A8B9C0D']);

        $this->get('/')
            ->assertSee('<meta name="google-site-verification" content="'.$code.'">', false)
            ->assertSee('<meta name="msvalidate.01" content="1A2B3C4D5E6F7A8B9C0D">', false);
    }

    public function test_anything_that_is_not_a_verification_code_is_not_printed(): void
    {
        Setting::query()->where('key', 'google_site_verification')->update(['value' => '"><script>alert(1)</script>']);

        $this->get('/')
            ->assertDontSee('google-site-verification', false)
            ->assertDontSee('<script>alert(1)', false);
    }

    public function test_every_page_describes_the_entity_and_the_site(): void
    {
        $nodes = collect($this->structuredData($this->get('/')));

        $organization = $nodes->firstWhere('@type', 'GovernmentOrganization');
        $this->assertSame(Setting::valueOf('site_name'), $organization['name']);
        $this->assertSame('PE', $organization['address']['addressCountry']);
        $this->assertContains(Setting::valueOf('facebook_url'), $organization['sameAs']);
        $this->assertSame('WebSite', $nodes->firstWhere('@type', 'WebSite')['@type']);
    }

    public function test_news_are_described_as_articles_with_their_breadcrumb(): void
    {
        $post = Post::factory()->create(['title' => 'Feria del cacao </script><script>alert(1)</script>']);

        $response = $this->get(route('posts.show', $post))
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertDontSee('</script><script>alert(1)', false);

        $article = collect($this->structuredData($response))->firstWhere('@type', 'NewsArticle');

        $this->assertSame($post->title, $article['headline']);
        $this->assertSame($post->published_at->toIso8601String(), $article['datePublished']);
        $this->assertContains('BreadcrumbList', $this->types($response));
    }

    public function test_only_open_job_postings_are_offered_to_google_jobs(): void
    {
        $open = JobPosting::factory()->create(['starts_at' => now()->subDays(2), 'ends_at' => now()->addDays(5), 'description' => '<p>Ingeniero agrónomo con experiencia.</p>']);
        $closed = JobPosting::factory()->create(['status' => JobPostingStatus::Finalizada]);
        $expired = JobPosting::factory()->create(['starts_at' => now()->subDays(20), 'ends_at' => now()->subDay()]);

        $posting = collect($this->structuredData($this->get(route('job-postings.show', $open))))->firstWhere('@type', 'JobPosting');

        $this->assertSame($open->title, $posting['title']);
        $this->assertSame('<p>Ingeniero agrónomo con experiencia.</p>', $posting['description']);
        $this->assertSame($open->code, $posting['identifier']['value']);
        $this->assertSame('Madre de Dios', $posting['jobLocation']['address']['addressRegion']);
        $this->assertNotContains('JobPosting', $this->types($this->get(route('job-postings.show', $closed))));
        $this->assertNotContains('JobPosting', $this->types($this->get(route('job-postings.show', $expired))));
    }

    public function test_the_canonical_address_keeps_meaningful_filters_only(): void
    {
        $this->get('/documentos?utm_source=facebook&q=tupa&tipo=resoluciones&page=2')
            ->assertSee('<link rel="canonical" href="'.url('/documentos').'?page=2&amp;tipo=resoluciones">', false);

        $this->get('/noticias?page=1&fbclid=abc')
            ->assertSee('<link rel="canonical" href="'.url('/noticias').'">', false);
    }

    public function test_the_panel_is_kept_out_of_search_results(): void
    {
        $this->get('/admin/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/')->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_the_ownership_codes_are_edited_in_the_portal_data(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/datos-del-portal?pestana=buscadores')
            ->assertOk()
            ->assertSee('Cómo publicar el portal en Google')
            ->assertSee(route('seo.sitemap'));

        Livewire::test(EditSettings::class)
            ->fillForm(['google_site_verification' => 'AbCdEf0123456789-_xyz'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('AbCdEf0123456789-_xyz', Setting::query()->where('key', 'google_site_verification')->value('value'));
    }
}
