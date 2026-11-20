<?php

namespace Tests\Feature;

use App\Enums\JobPostingStatus;
use App\Filament\Widgets\PendingTasks;
use App\Filament\Widgets\PublicationsChart;
use App\Filament\Widgets\WelcomeBanner;
use App\Models\ContactMessage;
use App\Models\JobPosting;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_banner_greets_the_user_with_their_roles(): void
    {
        $user = User::factory()->create(['name' => 'Rosa Quispe']);
        $user->roles()->attach(Role::factory()->withPermissions(['documentos' => ['view']])->create(['name' => 'Gestor documental']));
        $this->actingAs($user);

        Livewire::test(WelcomeBanner::class)
            ->assertSee('Rosa Quispe')
            ->assertSee('Gestor documental')
            ->assertSee('primer ingreso');
    }

    public function test_pending_tasks_only_include_the_user_modules(): void
    {
        ContactMessage::factory()->create();
        JobPosting::factory()->create(['status' => JobPostingStatus::EnCurso, 'ends_at' => now()->subDays(3)]);
        Post::factory()->create(['cover_image' => null]);

        $this->actingAs(User::factory()->withPermissions(['convocatorias' => ['view', 'update']])->create());

        Livewire::test(PendingTasks::class)
            ->assertSee('Convocatorias con plazo vencido')
            ->assertDontSee('Mensajes sin leer')
            ->assertDontSee('Noticias sin imagen de portada');
    }

    public function test_pending_tasks_show_all_clear_when_nothing_is_pending(): void
    {
        $this->actingAs(User::factory()->withPermissions(['mensajes' => ['view', 'update']])->create());

        Livewire::test(PendingTasks::class)->assertSee('¡Todo al día!');
    }

    public function test_publishers_are_told_about_drafts_and_admins_about_two_factor(): void
    {
        Post::factory()->draft()->create();

        $this->actingAs(User::factory()->withPermissions(['noticias' => ['view', 'create', 'update', 'publish']])->create());
        Livewire::test(PendingTasks::class)
            ->assertSee('Noticias en borrador')
            ->assertDontSee('Proteja su cuenta de administrador');

        $this->actingAs(User::factory()->withPermissions(['noticias' => ['view', 'create', 'update']])->create());
        Livewire::test(PendingTasks::class)->assertDontSee('Noticias en borrador');

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(PendingTasks::class)->assertSee('Proteja su cuenta de administrador');
    }

    public function test_publications_chart_renders(): void
    {
        Post::factory()->create(['published_at' => now()->subDay()]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(PublicationsChart::class)->assertOk();
    }

    public function test_list_pages_explain_their_purpose_and_offer_tabs(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Post::factory()->draft()->create();

        $this->get('/admin/noticias')
            ->assertOk()
            ->assertSee('Publique y edite las noticias del portal')
            ->assertSee('Borradores')
            ->assertSee('Programadas');

        $this->get('/admin/convocatorias?activeTab=vencidas')->assertOk()->assertSee('Plazo vencido');
        $this->get('/admin/mensajes')->assertOk()->assertSee('Sin leer');
    }

    public function test_panel_uses_the_institutional_logo_and_login_offers_a_way_back(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('images/logo-emblema.png', false)
            ->assertSee('Volver al portal institucional');
    }
}
