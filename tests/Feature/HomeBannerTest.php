<?php

namespace Tests\Feature;

use App\Enums\BannerOverlay;
use App\Filament\Resources\Slides\Pages\ManageSlides;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HomeBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_banners_show_their_background_photo_label_and_overlay(): void
    {
        Slide::factory()->create([
            'title' => 'Campaña de vacunación',
            'eyebrow' => 'Sanidad animal 2026',
            'background_image' => 'banners/fondos/vacunacion.jpg',
            'overlay' => BannerOverlay::Strong,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Campaña de vacunación')
            ->assertSee('Sanidad animal 2026')
            ->assertSee('/storage/banners/fondos/vacunacion.jpg', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('rgb(8 36 23 / 0.95)', false);
    }

    public function test_banners_appear_and_hide_on_their_scheduled_dates(): void
    {
        Slide::factory()->create(['title' => 'Banner vigente']);
        Slide::factory()->create(['title' => 'Banner programado', 'starts_at' => now()->addDay()]);
        Slide::factory()->create(['title' => 'Banner vencido', 'ends_at' => now()->subHour()]);
        Slide::factory()->create(['title' => 'Banner apagado', 'is_active' => false]);

        $this->get('/')
            ->assertSee('Banner vigente')
            ->assertDontSee('Banner programado')
            ->assertDontSee('Banner vencido')
            ->assertDontSee('Banner apagado');

        $this->assertSame(
            ['Visible', 'Programado', 'Finalizado', 'Desactivado'],
            Slide::query()->orderBy('id')->get()->map(fn (Slide $slide): string => $slide->visibility()['label'])->all(),
        );
    }

    public function test_a_rotating_hero_can_be_paused(): void
    {
        Slide::factory()->count(2)->create();

        $this->get('/')->assertSee('Pausar el carrusel');
    }

    public function test_administrators_create_banners_with_background_and_side_images(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSlides::class)
            ->callAction('create', data: [
                'title' => 'Feria agropecuaria 2026',
                'eyebrow' => 'Del 10 al 12 de diciembre',
                'subtitle' => 'Productores de las tres provincias.',
                'button_text' => 'Ver programa',
                'button_url' => '/noticias',
                'background_image' => UploadedFile::fake()->image('fondo.jpg', 1920, 800),
                'image' => UploadedFile::fake()->image('lateral.jpg', 1200, 900),
                'overlay' => 'light',
                'starts_at' => now()->addDays(3)->format('Y-m-d H:i'),
                'ends_at' => now()->addDays(10)->format('Y-m-d H:i'),
                'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        $slide = Slide::query()->where('title', 'Feria agropecuaria 2026')->sole();

        $this->assertSame(BannerOverlay::Light, $slide->overlay);
        $this->assertStringStartsWith('banners/fondos/', $slide->background_image);
        Storage::disk('public')->assertExists($slide->background_image);
        Storage::disk('public')->assertExists($slide->image);
        $this->assertSame('Programado', $slide->visibility()['label']);
    }

    public function test_the_end_date_must_follow_the_start_date(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSlides::class)
            ->callAction('create', data: [
                'title' => 'Fechas al revés',
                'starts_at' => now()->addDays(5)->format('Y-m-d H:i'),
                'ends_at' => now()->addDay()->format('Y-m-d H:i'),
            ])
            ->assertHasActionErrors(['ends_at']);
    }

    public function test_the_banner_list_offers_preview_tabs(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Slide::factory()->create(['title' => 'Sin foto']);

        $this->get('/admin/banners')
            ->assertOk()
            ->assertSee('Visibles ahora')
            ->assertSee('Sin imagen de fondo')
            ->assertSee('Ver en la portada');
    }
}
