<?php

namespace Tests\Feature;

use App\Enums\DocumentSection;
use App\Enums\JobPostingStatus;
use App\Models\Agency;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\JobPosting;
use App\Models\OrganizationalUnit;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function staticRoutes(): array
    {
        return [
            'inicio' => ['/'],
            'noticias' => ['/noticias'],
            'documentos' => ['/documentos'],
            'convocatorias' => ['/convocatorias'],
            'nosotros' => ['/institucional/nosotros'],
            'organigrama' => ['/institucional/organigrama'],
            'directorio' => ['/institucional/directorio'],
            'agencias' => ['/agencias-agrarias'],
            'información agraria' => ['/informacion-agraria'],
            'transparencia' => ['/transparencia'],
            'contacto' => ['/contacto'],
            'buscar' => ['/buscar'],
        ];
    }

    #[DataProvider('staticRoutes')]
    public function test_public_pages_render_with_an_empty_database(string $uri): void
    {
        $this->get($uri)->assertOk();
    }

    public function test_header_offers_login_to_visitors_and_the_panel_to_signed_in_users(): void
    {
        $this->get('/')
            ->assertSee('Iniciar sesión')
            ->assertSee(route('filament.admin.auth.login'), false);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/')
            ->assertSee('Panel de administración')
            ->assertDontSee('Iniciar sesión');
    }

    public function test_every_page_offers_the_accessibility_toolbar(): void
    {
        foreach (['/', '/noticias', '/documentos', '/contacto'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('Opciones de accesibilidad')
                ->assertSee('aria-controls="panel-accesibilidad"', false)
                ->assertSeeInOrder(['Tamaño del texto', 'Alto contraste', 'Escala de grises', 'Resaltar enlaces', 'Fuente para dislexia', 'Detener animaciones', 'Leer en voz alta', 'Restablecer configuración'])
                ->assertSee('Ley N° 29973')
                ->assertSee('window.applyAccessibility', false);
        }
    }

    public function test_menu_lists_offices_with_their_units_like_the_former_portal(): void
    {
        $root = OrganizationalUnit::factory()->create(['name' => 'Dirección Regional']);
        $office = OrganizationalUnit::factory()->create(['name' => 'Oficina de Administración', 'parent_id' => $root->id]);
        OrganizationalUnit::factory()->create(['name' => 'Unidad de Tesorería', 'parent_id' => $office->id]);
        OrganizationalUnit::factory()->create(['name' => 'Unidad Oculta', 'parent_id' => $office->id, 'is_active' => false]);
        Agency::factory()->create(['name' => 'Agencia Agraria Tahuamanu']);
        DocumentType::factory()->section(DocumentSection::InformacionAgraria)->create(['name' => 'Estadística Pecuaria']);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Órganos y unidades', 'Dirección Regional', 'Oficina de Administración', 'Unidad de Tesorería'])
            ->assertSee('Agencia Agraria Tahuamanu')
            ->assertSee('Estadística Pecuaria')
            ->assertDontSee('Unidad Oculta');
    }

    public function test_menu_reflects_changes_made_in_the_panel(): void
    {
        $office = OrganizationalUnit::factory()->create(['name' => 'Oficina de Asesoría Jurídica']);
        $this->get('/')->assertSee('Oficina de Asesoría Jurídica');

        $office->update(['name' => 'Oficina de Asesoría Legal']);

        $this->get('/')->assertSee('Oficina de Asesoría Legal')->assertDontSee('Oficina de Asesoría Jurídica');
    }

    public function test_home_shows_latest_published_content(): void
    {
        $post = Post::factory()->create(['title' => 'Campaña de vacunación de ganado']);
        $document = Document::factory()->create(['title' => 'Resolución de prueba 2026']);
        $jobPosting = JobPosting::factory()->create(['code' => 'CAS N° 099-2026']);

        $this->get('/')
            ->assertOk()
            ->assertSee($post->title)
            ->assertSee($document->title)
            ->assertSee($jobPosting->code);
    }

    public function test_home_highlights_only_published_agrarian_information(): void
    {
        $agrarianType = DocumentType::factory()->section(DocumentSection::InformacionAgraria)->create();
        $bulletin = Document::factory()->for($agrarianType)->create(['title' => 'Boletín estadístico octubre 2026']);
        $hiddenBulletin = Document::factory()->for($agrarianType)->unpublished()->create(['title' => 'Boletín borrador noviembre 2026']);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Últimas publicaciones', $bulletin->title])
            ->assertDontSee($hiddenBulletin->title);
    }

    public function test_home_omits_agrarian_block_list_when_there_are_no_bulletins(): void
    {
        $resolutionType = DocumentType::factory()->section(DocumentSection::Normativa)->create();
        Document::factory()->for($resolutionType)->create(['title' => 'Resolución Directoral N° 001-2026']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Boletines estadísticos y precios de mercado')
            ->assertDontSee('Últimas publicaciones');
    }

    public function test_draft_and_scheduled_posts_are_hidden(): void
    {
        $visible = Post::factory()->create();
        $draft = Post::factory()->draft()->create();
        $scheduled = Post::factory()->scheduled()->create();

        $this->get('/noticias')
            ->assertSee($visible->title)
            ->assertDontSee($draft->title)
            ->assertDontSee($scheduled->title);

        $this->get(route('posts.show', $visible))->assertOk();
        $this->get(route('posts.show', $draft))->assertNotFound();
        $this->get(route('posts.show', $scheduled))->assertNotFound();
    }

    public function test_viewing_a_post_increments_its_views(): void
    {
        $post = Post::factory()->create();

        $this->get(route('posts.show', $post))->assertOk();

        $this->assertSame(1, $post->fresh()->views);
    }

    public function test_posts_can_be_filtered_by_category(): void
    {
        $campaigns = Category::factory()->create(['name' => 'Campañas']);
        $inCategory = Post::factory()->for($campaigns)->create();
        $otherPost = Post::factory()->create();

        $this->get('/noticias?categoria='.$campaigns->slug)
            ->assertSee($inCategory->title)
            ->assertDontSee($otherPost->title);
    }

    public function test_documents_can_be_filtered_by_section_type_year_and_term(): void
    {
        $resolutions = DocumentType::factory()->section(DocumentSection::Normativa)->create();
        $management = DocumentType::factory()->section(DocumentSection::Gestion)->create();

        $resolution2026 = Document::factory()->for($resolutions)->create(['title' => 'Resolución de saneamiento', 'issued_at' => '2026-03-01']);
        $resolution2023 = Document::factory()->for($resolutions)->create(['title' => 'Resolución antigua', 'issued_at' => '2023-03-01']);
        $tupa = Document::factory()->for($management)->create(['title' => 'Texto Único de Procedimientos', 'code' => 'TUPA']);
        $hidden = Document::factory()->for($management)->unpublished()->create();

        $this->get('/documentos?seccion=gestion')
            ->assertSee($tupa->title)
            ->assertDontSee($resolution2026->title)
            ->assertDontSee($hidden->title);

        $this->get('/documentos?tipo='.$resolutions->slug.'&anio=2026')
            ->assertSee($resolution2026->title)
            ->assertDontSee($resolution2023->title);

        $this->get('/documentos?q=TUPA')
            ->assertSee($tupa->title)
            ->assertDontSee($resolution2026->title);
    }

    public function test_unpublished_records_return_not_found(): void
    {
        $this->get(route('documents.show', Document::factory()->unpublished()->create()))->assertNotFound();
        $this->get(route('job-postings.show', JobPosting::factory()->create(['is_published' => false])))->assertNotFound();
        $this->get(route('pages.show', Page::factory()->create(['is_published' => false])))->assertNotFound();
        $this->get(route('institutional.unit', OrganizationalUnit::factory()->create(['is_active' => false])))->assertNotFound();
        $this->get(route('agencies.show', Agency::factory()->create(['is_active' => false])))->assertNotFound();
    }

    public function test_job_postings_can_be_filtered_by_status(): void
    {
        $open = JobPosting::factory()->create();
        $closed = JobPosting::factory()->status(JobPostingStatus::Finalizada)->create();

        $this->get('/convocatorias?estado=en-curso')
            ->assertSee($open->code)
            ->assertDontSee($closed->code);

        $this->get(route('job-postings.show', $closed))->assertOk()->assertSee('Finalizada');
    }

    public function test_search_requires_three_characters_and_finds_content(): void
    {
        $document = Document::factory()->create(['title' => 'Plan Operativo Institucional 2026']);

        $this->get('/buscar?q=pl')->assertOk()->assertSee('Escriba al menos 3 caracteres');
        $this->get('/buscar?q=Operativo')->assertOk()->assertSee($document->title);
    }

    public function test_unit_page_lists_its_children_and_documents(): void
    {
        $office = OrganizationalUnit::factory()->create();
        $child = OrganizationalUnit::factory()->create(['parent_id' => $office->id]);
        $document = Document::factory()->create(['organizational_unit_id' => $office->id]);

        $this->get(route('institutional.unit', $office))
            ->assertOk()
            ->assertSee($child->name)
            ->assertSee($document->title);
    }

    public function test_rich_text_is_sanitized_before_rendering(): void
    {
        $page = Page::factory()->create(['body' => '<p>Contenido seguro</p><script>alert("xss")</script>']);

        $this->get(route('pages.show', $page))
            ->assertOk()
            ->assertSee('Contenido seguro')
            ->assertDontSee('alert("xss")', false);
    }
}
