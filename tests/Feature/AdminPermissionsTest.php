<?php

namespace Tests\Feature;

use App\Filament\Widgets\QuickActions;
use App\Filament\Widgets\RecentActivity;
use App\Models\ContactMessage;
use App\Models\Document;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private const ALL = ['view', 'create', 'update', 'delete', 'publish'];

    public function test_users_only_reach_the_modules_their_roles_grant(): void
    {
        $documentsEditor = User::factory()->withPermissions(['documentos' => self::ALL])->create();

        $this->actingAs($documentsEditor);
        $this->get('/admin')->assertOk();
        $this->get('/admin/documentos')->assertOk();
        $this->get('/admin/documentos/create')->assertOk();
        $this->get('/admin/noticias')->assertForbidden();
        $this->get('/admin/convocatorias')->assertForbidden();
        $this->get('/admin/mensajes')->assertForbidden();
    }

    public function test_configuration_modules_require_their_own_permissions(): void
    {
        $editor = User::factory()->withPermissions(['noticias' => self::ALL, 'unidades' => ['view', 'update']])->create();

        $this->actingAs($editor);
        $this->get('/admin/noticias')->assertOk();
        $this->get('/admin/usuarios')->assertForbidden();
        $this->get('/admin/roles')->assertForbidden();
        $this->get('/admin/datos-del-portal')->assertForbidden();
        $this->get('/admin/registro-de-actividad')->assertForbidden();
        $this->get('/admin/redirecciones')->assertForbidden();

        $this->actingAs(User::factory()->admin()->create());
        $this->get('/admin/usuarios')->assertOk();
        $this->get('/admin/roles')->assertOk();
        $this->get('/admin/registro-de-actividad')->assertOk();
    }

    public function test_inactive_accounts_and_users_without_roles_cannot_enter(): void
    {
        $this->actingAs(User::factory()->admin()->inactive()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->withPermissions(['noticias' => self::ALL])->inactive()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_dashboard_shortcuts_match_the_user_permissions(): void
    {
        $this->actingAs(User::factory()->withPermissions(['documentos' => self::ALL])->create());

        Livewire::test(QuickActions::class)
            ->assertSee('Subir documento')
            ->assertDontSee('Publicar noticia');

        $this->assertFalse(RecentActivity::canView());
    }

    public function test_writers_without_publish_permission_get_a_drafting_shortcut(): void
    {
        $this->actingAs(User::factory()->withPermissions(['noticias' => ['view', 'create', 'update']])->create());

        Livewire::test(QuickActions::class)
            ->assertSee('Redactar noticia')
            ->assertDontSee('Publicar noticia');
    }

    public function test_administrators_see_every_shortcut_and_the_recent_activity(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(QuickActions::class)
            ->assertSee('Publicar noticia')
            ->assertSee('Nueva convocatoria')
            ->assertSee('Responder mensajes');

        $this->assertTrue(RecentActivity::canView());
    }

    public function test_each_action_needs_its_own_permission(): void
    {
        $reader = User::factory()->withPermissions(['noticias' => ['view']])->create();
        $post = Post::factory()->draft()->create();

        $this->assertTrue($reader->can('viewAny', Post::class));
        $this->assertFalse($reader->can('create', Post::class));
        $this->assertFalse($reader->can('update', $post));
        $this->assertFalse($reader->can('delete', $post));
        $this->assertFalse($reader->can('viewAny', User::class));
        $this->assertFalse($reader->can('viewAny', Document::class));

        $this->actingAs($reader);
        $this->get('/admin/noticias')->assertOk();
        $this->get('/admin/noticias/create')->assertForbidden();
        $this->get("/admin/noticias/{$post->id}/edit")->assertForbidden();
    }

    public function test_actions_a_module_does_not_offer_are_denied_even_to_administrators(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('create', ContactMessage::class));
        $this->assertFalse($admin->can('create', Setting::class));
        $this->assertTrue($admin->can('update', Setting::factory()->create()));
    }

    public function test_administrators_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('delete', $admin));
        $this->assertTrue($admin->can('delete', User::factory()->create()));
    }
}
