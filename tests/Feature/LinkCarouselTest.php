<?php

namespace Tests\Feature;

use App\Enums\LinkGroup;
use App\Filament\Resources\Links\Pages\ManageLinks;
use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LinkCarouselTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_carousel_shows_only_active_links_marked_for_it(): void
    {
        Link::factory()->create(['name' => 'SENASA', 'description' => 'Servicio Nacional de Sanidad Agraria', 'logo' => 'enlaces/senasa.jpg', 'sort_order' => 2]);
        Link::factory()->create(['name' => 'AgroRural', 'sort_order' => 1]);
        Link::factory()->withoutCarousel()->create(['name' => 'Enlace solo en el pie']);
        Link::factory()->inactive()->create(['name' => 'Enlace desactivado']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Enlaces de interés')
            ->assertSee('Pausar')
            ->assertSee('alt="SENASA: Servicio Nacional de Sanidad Agraria"', false)
            ->assertSee('/storage/enlaces/senasa.jpg', false)
            ->assertSeeInOrder(['AgroRural', 'SENASA'])
            ->assertDontSee('Enlace solo en el pie')
            ->assertDontSee('Enlace desactivado');
    }

    public function test_only_the_first_copy_of_each_link_is_reachable_by_keyboard_and_screen_readers(): void
    {
        Link::factory()->count(3)->sequence(['name' => 'INIA'], ['name' => 'ANA'], ['name' => 'Agrobanco'])->create();

        $html = $this->get('/')->getContent();
        $start = (int) strpos($html, 'link-carousel-track');
        $carousel = substr($html, $start, (int) strpos($html, '</section>', $start) - $start);

        $this->assertSame(3, substr_count($carousel, '<span class="sr-only">(se abre en una pestaña nueva)</span>') - substr_count($carousel, 'tabindex="-1"'));
        $this->assertStringContainsString('aria-hidden="true" data-duplicate', $carousel);
    }

    public function test_home_has_no_carousel_when_no_link_is_marked(): void
    {
        Link::factory()->withoutCarousel()->create();

        $this->get('/')->assertOk()->assertDontSee('titulo-enlaces', false);
    }

    public function test_administrators_add_links_with_a_logo_to_the_carousel(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageLinks::class)
            ->callAction('create', data: [
                'name' => 'SERFOR',
                'group' => LinkGroup::EnlacesInteres->value,
                'url' => 'https://www.gob.pe/serfor',
                'description' => 'Servicio Nacional Forestal y de Fauna Silvestre',
                'logo' => UploadedFile::fake()->image('serfor.png', 300, 90),
                'show_in_carousel' => true,
                'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        $link = Link::query()->where('name', 'SERFOR')->sole();

        $this->assertTrue($link->show_in_carousel);
        Storage::disk('public')->assertExists($link->logo);
        $this->get('/')->assertSee('alt="SERFOR: Servicio Nacional Forestal y de Fauna Silvestre"', false);
    }

    public function test_links_page_offers_carousel_tabs(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Link::factory()->create(['name' => 'INIA']);

        $this->get('/admin/enlaces')
            ->assertOk()
            ->assertSee('En el carrusel')
            ->assertSee('Sin logo')
            ->assertSee('Ver carrusel en la portada');
    }
}
