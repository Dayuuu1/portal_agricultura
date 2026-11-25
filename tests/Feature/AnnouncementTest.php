<?php

namespace Tests\Feature;

use App\Enums\AnnouncementFrequency;
use App\Enums\AnnouncementKind;
use App\Enums\AnnouncementLayout;
use App\Enums\AnnouncementPlacement;
use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Models\Announcement;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Markup of the announcement window alone, so the rest of the page (the news list shows the same posts) does not count.
     */
    private function windowOf(TestResponse $response): string
    {
        $html = (string) $response->getContent();

        $this->assertStringContainsString('<dialog', $html, 'The page has no announcement window.');

        return Str::between($html, '<dialog', '</dialog>');
    }

    public function test_the_home_page_opens_the_window_with_the_current_announcement(): void
    {
        Announcement::factory()->create([
            'kind' => AnnouncementKind::Alert,
            'title' => 'Suspensión de atención por feriado',
            'body' => 'El lunes 8 no habrá atención presencial.',
            'button_text' => 'Ver horarios',
            'button_url' => '/contacto',
        ]);

        $window = $this->windowOf($this->get('/')->assertOk());

        $this->assertStringContainsString('Aviso importante', $window);
        $this->assertStringContainsString('Suspensión de atención por feriado', $window);
        $this->assertStringContainsString('El lunes 8 no habrá atención presencial.', $window);
        $this->assertStringContainsString('href="/contacto"', $window);
        $this->assertStringContainsString('Ver horarios', $window);
    }

    public function test_pages_without_announcements_carry_no_window(): void
    {
        $this->get('/')->assertOk()->assertDontSee('announcement-dialog', false);
    }

    public function test_announcements_appear_and_hide_on_their_scheduled_dates(): void
    {
        Announcement::factory()->create(['title' => 'Anuncio vigente']);
        Announcement::factory()->create(['title' => 'Anuncio programado', 'starts_at' => now()->addDay()]);
        Announcement::factory()->create(['title' => 'Anuncio vencido', 'ends_at' => now()->subHour()]);
        Announcement::factory()->create(['title' => 'Anuncio apagado', 'is_active' => false]);

        $this->get('/')
            ->assertSee('Anuncio vigente')
            ->assertDontSee('Anuncio programado')
            ->assertDontSee('Anuncio vencido')
            ->assertDontSee('Anuncio apagado');

        $this->assertSame(
            ['Visible', 'Programado', 'Finalizado', 'Desactivado'],
            Announcement::query()->orderBy('id')->get()->map(fn (Announcement $announcement): string => $announcement->visibility()['label'])->all(),
        );
    }

    public function test_a_home_page_announcement_stays_off_the_other_pages(): void
    {
        Announcement::factory()->create(['title' => 'Solo en la portada']);
        Announcement::factory()->everywhere()->create(['title' => 'En todo el portal']);

        $this->get('/noticias')
            ->assertOk()
            ->assertSee('En todo el portal')
            ->assertDontSee('Solo en la portada');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pagesWithoutWindow(): array
    {
        return [
            'contacto' => ['/contacto'],
            'libro de reclamaciones' => ['/libro-de-reclamaciones'],
            'mesa de partes' => ['/mesa-de-partes'],
            'página no encontrada' => ['/esta-pagina-no-existe'],
        ];
    }

    #[DataProvider('pagesWithoutWindow')]
    public function test_forms_and_utility_pages_never_open_the_window(string $uri): void
    {
        Announcement::factory()->everywhere()->create();

        $this->get($uri)->assertDontSee('announcement-dialog', false);
    }

    public function test_a_highlighted_post_lends_its_summary_cover_and_link(): void
    {
        $post = Post::factory()->create(['excerpt' => 'Productores de las tres provincias.', 'cover_image' => 'noticias/feria.jpg']);
        Announcement::factory()->forPost($post)->create(['title' => 'No se pierda la feria']);

        $window = $this->windowOf($this->get('/'));

        $this->assertStringContainsString('No se pierda la feria', $window);
        $this->assertStringContainsString('Productores de las tres provincias.', $window);
        $this->assertStringContainsString('/storage/noticias/feria.jpg', $window);
        $this->assertStringContainsString('href="'.route('posts.show', $post).'"', $window);
        $this->assertStringContainsString('Leer la noticia', $window);
    }

    public function test_a_highlighted_post_waits_until_it_is_published(): void
    {
        $announcement = Announcement::factory()->forPost(Post::factory()->draft()->create())->create();

        $this->get('/')->assertDontSee('announcement-dialog', false);
        $this->assertSame('Noticia sin publicar', $announcement->visibility()['label']);
    }

    public function test_the_window_stays_closed_on_the_page_it_invites_to(): void
    {
        $post = Post::factory()->create();
        Announcement::factory()->everywhere()->forPost($post)->create();

        $this->get(route('posts.show', $post))->assertOk()->assertDontSee('announcement-dialog', false);
        $this->get('/noticias')->assertSee('announcement-dialog', false);
    }

    public function test_a_poster_shows_the_whole_image_described_by_its_title(): void
    {
        Announcement::factory()->create([
            'title' => 'Campaña de vacunación de ganado',
            'body' => 'Del 1 al 15 de diciembre en Tambopata.',
            'image' => 'anuncios/afiche.jpg',
            'layout' => AnnouncementLayout::Poster,
        ]);

        $window = $this->windowOf($this->get('/'));

        $this->assertStringContainsString('alt="Campaña de vacunación de ganado"', $window);
        $this->assertStringContainsString('object-contain', $window);
        $this->assertStringContainsString('<p class="sr-only">Del 1 al 15 de diciembre en Tambopata.</p>', $window);
    }

    public function test_links_saved_before_validation_are_never_printed(): void
    {
        Announcement::factory()->create(['title' => 'Enlace antiguo', 'button_url' => 'javascript:alert(1)']);

        $window = $this->windowOf($this->get('/'));

        $this->assertStringContainsString('Enlace antiguo', $window);
        $this->assertStringNotContainsString('javascript:', $window);
        $this->assertStringContainsString('Entendido', $window);
    }

    public function test_the_announcement_text_is_escaped(): void
    {
        Announcement::factory()->create([
            'title' => '<script>alert("x")</script> Aviso',
            'body' => '<img src=x onerror=alert(1)>',
        ]);

        $window = $this->windowOf($this->get('/'));

        $this->assertStringContainsString('&lt;script&gt;', $window);
        $this->assertStringNotContainsString('<script>alert', $window);
        $this->assertStringNotContainsString('<img src=x', $window);
    }

    public function test_only_staff_with_access_to_announcements_can_preview_one_on_the_portal(): void
    {
        $announcement = Announcement::factory()->create(['title' => 'Campaña en preparación', 'is_active' => false]);
        $url = '/?anuncio='.$announcement->id;

        $this->get($url)->assertOk()->assertDontSee('Campaña en preparación');

        $this->actingAs(User::factory()->withPermissions(['noticias' => ['view']])->create())
            ->get($url)
            ->assertDontSee('Campaña en preparación');

        $this->actingAs(User::factory()->withPermissions(['anuncios' => ['view']])->create())
            ->get($url)
            ->assertSee('Campaña en preparación')
            ->assertSee('Vista previa: solo usted ve esta ventana.');
    }

    public function test_editors_create_announcements_from_the_panel(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->withPermissions(['anuncios' => ['view', 'create', 'update', 'delete']])->create());

        Livewire::test(CreateAnnouncement::class)
            ->fillForm([
                'kind' => 'event',
                'title' => 'Feria agropecuaria 2026',
                'body' => 'Del 10 al 12 de diciembre, plaza de armas de Puerto Maldonado.',
                'button_text' => 'Ver programa',
                'button_url' => '/noticias',
                'layout' => 'poster',
                'image' => UploadedFile::fake()->image('afiche.jpg', 1080, 1350),
                'placement' => 'everywhere',
                'frequency' => 'daily',
                'starts_at' => now()->addDays(3)->format('Y-m-d H:i'),
                'ends_at' => now()->addDays(10)->format('Y-m-d H:i'),
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin/anuncios');

        $announcement = Announcement::query()->where('title', 'Feria agropecuaria 2026')->sole();

        $this->assertSame(AnnouncementKind::Event, $announcement->kind);
        $this->assertSame(AnnouncementLayout::Poster, $announcement->layout);
        $this->assertSame(AnnouncementPlacement::Everywhere, $announcement->placement);
        $this->assertSame(AnnouncementFrequency::Daily, $announcement->frequency);
        $this->assertStringStartsWith('anuncios/', $announcement->image);
        Storage::disk('public')->assertExists($announcement->image);
        $this->assertSame('Programado', $announcement->visibility()['label']);
    }

    public function test_editors_change_an_announcement_on_its_own_page(): void
    {
        $this->actingAs(User::factory()->withPermissions(['anuncios' => ['view', 'update']])->create());
        $announcement = Announcement::factory()->create(['title' => 'Horario de atención']);

        $this->get('/admin/anuncios/'.$announcement->id.'/edit')
            ->assertOk()
            ->assertSee('Vista previa')
            ->assertSee('href="'.route('home', ['anuncio' => $announcement->id]).'"', false);

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->getRouteKey()])
            ->fillForm(['title' => 'Nuevo horario de atención', 'frequency' => 'visit'])
            ->call('save')
            ->assertHasNoFormErrors();

        $announcement->refresh();
        $this->assertSame('Nuevo horario de atención', $announcement->title);
        $this->assertSame(AnnouncementFrequency::EveryVisit, $announcement->frequency);
    }

    public function test_the_button_link_must_be_a_portal_path_or_web_address(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['title' => 'Enlace peligroso', 'button_url' => 'javascript:alert(1)'])
            ->call('create')
            ->assertHasFormErrors(['button_url']);
    }

    public function test_the_end_date_must_follow_the_start_date(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Fechas al revés',
                'starts_at' => now()->addDays(5)->format('Y-m-d H:i'),
                'ends_at' => now()->addDay()->format('Y-m-d H:i'),
            ])
            ->call('create')
            ->assertHasFormErrors(['ends_at']);
    }

    public function test_the_panel_section_requires_the_announcements_permission(): void
    {
        $this->actingAs(User::factory()->withPermissions(['noticias' => ['view']])->create())
            ->get('/admin/anuncios')
            ->assertForbidden();

        $this->actingAs(User::factory()->withPermissions(['anuncios' => ['view']])->create());

        $this->get('/admin/anuncios')->assertOk()->assertSee('Visibles ahora');
        $this->get('/admin/anuncios/create')->assertForbidden();
    }
}
