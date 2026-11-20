<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Document;
use App\Models\JobPosting;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function adminPages(): array
    {
        return [
            'escritorio' => ['/admin'],
            'perfil' => ['/admin/profile'],
            'noticias' => ['/admin/noticias'],
            'nueva noticia' => ['/admin/noticias/create'],
            'categorías' => ['/admin/categorias'],
            'páginas' => ['/admin/paginas'],
            'banners' => ['/admin/banners'],
            'documentos' => ['/admin/documentos'],
            'nuevo documento' => ['/admin/documentos/create'],
            'tipos de documento' => ['/admin/tipos-de-documento'],
            'convocatorias' => ['/admin/convocatorias'],
            'nueva convocatoria' => ['/admin/convocatorias/create'],
            'unidades orgánicas' => ['/admin/unidades-organicas'],
            'nueva unidad' => ['/admin/unidades-organicas/create'],
            'agencias' => ['/admin/agencias-agrarias'],
            'enlaces' => ['/admin/enlaces'],
            'mensajes' => ['/admin/mensajes'],
            'ajustes' => ['/admin/datos-del-portal'],
            'usuarios' => ['/admin/usuarios'],
            'registro de actividad' => ['/admin/registro-de-actividad'],
            'redirecciones' => ['/admin/redirecciones'],
        ];
    }

    public function test_guests_are_redirected_to_the_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_users_without_admin_flag_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin')
            ->assertForbidden();
    }

    #[DataProvider('adminPages')]
    public function test_admins_can_open_every_section(string $uri): void
    {
        $this->seed(SettingSeeder::class);
        Post::factory()->create();
        Document::factory()->create();
        JobPosting::factory()->create();
        ContactMessage::factory()->create();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get($uri)
            ->assertOk();
    }

    public function test_edit_pages_load_for_existing_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/noticias/'.Post::factory()->create()->id.'/edit')->assertOk();
        $this->actingAs($admin)->get('/admin/documentos/'.Document::factory()->withFile()->create()->id.'/edit')->assertOk();
        $this->actingAs($admin)->get('/admin/convocatorias/'.JobPosting::factory()->create()->id.'/edit')->assertOk();
    }
}
