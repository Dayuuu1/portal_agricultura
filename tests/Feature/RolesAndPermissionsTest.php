<?php

namespace Tests\Feature;

use App\Enums\DocumentSection;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private const ALL = ['view', 'create', 'update', 'delete', 'publish'];

    private const WRITER = ['view', 'create', 'update', 'delete'];

    public function test_writers_without_publish_permission_can_only_save_drafts(): void
    {
        $this->actingAs(User::factory()->withPermissions(['noticias' => self::WRITER])->create());

        Livewire::test(CreatePost::class)
            ->fillForm(['title' => 'Campaña de vacunación de ganado', 'published_at' => now(), 'is_published' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertFalse(Post::query()->where('title', 'Campaña de vacunación de ganado')->firstOrFail()->is_published);
    }

    public function test_publishers_can_put_content_live(): void
    {
        $this->actingAs(User::factory()->withPermissions(['noticias' => self::ALL])->create());

        Livewire::test(CreatePost::class)
            ->fillForm(['title' => 'Feria agropecuaria regional', 'published_at' => now(), 'is_published' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(Post::query()->where('title', 'Feria agropecuaria regional')->firstOrFail()->is_published);
    }

    public function test_published_content_is_locked_for_users_without_publish_permission(): void
    {
        $writer = User::factory()->withPermissions(['noticias' => self::WRITER])->create();
        $draft = Post::factory()->draft()->create();
        $published = Post::factory()->create();

        $this->assertTrue($writer->can('update', $draft));
        $this->assertTrue($writer->can('delete', $draft));
        $this->assertFalse($writer->can('update', $published));
        $this->assertFalse($writer->can('delete', $published));

        $this->actingAs($writer)->get("/admin/noticias/{$published->id}/edit")->assertForbidden();
    }

    public function test_document_roles_can_be_limited_to_some_types(): void
    {
        $statistics = DocumentType::factory()->section(DocumentSection::InformacionAgraria)->create(['name' => 'Estadística Pecuaria']);
        $resolutions = DocumentType::factory()->create(['name' => 'Resoluciones Directorales']);
        $bulletin = Document::factory()->for($statistics)->create(['title' => 'Boletín pecuario octubre']);
        $resolution = Document::factory()->for($resolutions)->create(['title' => 'Resolución de nombramiento']);

        $statistician = User::factory()->withPermissions(['documentos' => self::ALL], [$statistics->id])->create();

        $this->assertSame([$statistics->id], $statistician->allowedDocumentTypeIds());
        $this->assertTrue($statistician->can('update', $bulletin));
        $this->assertFalse($statistician->can('update', $resolution));

        $this->actingAs($statistician);
        $this->get('/admin/documentos')->assertOk()->assertSee('Boletín pecuario octubre')->assertDontSee('Resolución de nombramiento');
        $this->get("/admin/documentos/{$resolution->id}/edit")->assertNotFound();

        Livewire::test(CreateDocument::class)
            ->fillForm(['title' => 'Documento fuera de su área', 'document_type_id' => $resolutions->id, 'period_year' => 2026])
            ->call('create')
            ->assertHasFormErrors(['document_type_id']);
    }

    public function test_bulk_upload_respects_type_limits_and_publish_permission(): void
    {
        Storage::fake('public');
        $statistics = DocumentType::factory()->section(DocumentSection::InformacionAgraria)->create();
        $resolutions = DocumentType::factory()->create();
        $this->actingAs(User::factory()->withPermissions(['documentos' => self::WRITER], [$statistics->id])->create());

        $upload = fn (DocumentType $type) => Livewire::test(ListDocuments::class)->callAction('bulkUpload', data: [
            'document_type_id' => $type->id,
            'period_year' => 2026,
            'issued_at' => '2026-11-15',
            'files' => [UploadedFile::fake()->create('Precios octubre 2026.pdf', 20, 'application/pdf')],
            'is_published' => true,
        ]);

        $upload($resolutions)->assertHasActionErrors(['document_type_id']);
        $upload($statistics)->assertHasNoActionErrors();

        $this->assertSame(1, Document::query()->count());
        $this->assertFalse(Document::query()->sole()->is_published);
    }

    public function test_roles_add_up_and_an_unrestricted_role_allows_every_type(): void
    {
        [$first, $second] = DocumentType::factory()->count(2)->create();
        $user = User::factory()->create();
        $user->roles()->attach(Role::factory()->withPermissions(['documentos' => ['view']])->create(['document_type_ids' => [$first->id]]));
        $user->roles()->attach(Role::factory()->withPermissions(['documentos' => ['view', 'create']])->create(['document_type_ids' => [$second->id]]));
        $user->refreshPermissions();

        $this->assertEqualsCanonicalizing([$first->id, $second->id], $user->allowedDocumentTypeIds());
        $this->assertTrue($user->hasPermission('documentos.create'));
        $this->assertFalse($user->hasPermission('documentos.delete'));

        $user->roles()->attach(Role::factory()->withPermissions(['documentos' => ['view']])->create());
        $user->refreshPermissions();

        $this->assertNull($user->allowedDocumentTypeIds());
    }

    public function test_permissions_are_sanitized_and_imply_the_basic_actions(): void
    {
        $role = Role::factory()->create(['permissions' => [
            'noticias' => ['publish'],
            'mensajes' => ['create', 'view'],
            'banners' => ['nonexistent'],
            'modulo_inventado' => ['view'],
        ]]);

        $this->assertSame([
            'noticias' => ['view', 'update', 'publish'],
            'mensajes' => ['view'],
        ], $role->fresh()->permissions);
    }

    public function test_user_managers_cannot_hand_out_permissions_they_do_not_have(): void
    {
        $covered = Role::factory()->withPermissions(['noticias' => ['view']])->create(['name' => 'Lector de noticias']);
        $broader = Role::factory()->withPermissions(['documentos' => self::ALL])->create(['name' => 'Gestor documental']);
        $this->actingAs(User::factory()->withPermissions(['usuarios' => ['view', 'create', 'update', 'delete'], 'noticias' => self::ALL])->create());

        $this->assertContains($covered->id, UserResource::assignableRoleIds());
        $this->assertNotContains($broader->id, UserResource::assignableRoleIds());

        $newUser = fn (Role $role, string $email) => Livewire::test(ManageUsers::class)->callAction('create', data: [
            'name' => 'Nueva persona',
            'email' => $email,
            'password' => 'ClaveSegura2026',
            'is_active' => true,
            'is_admin' => true,
            'roles' => [$role->id],
        ]);

        $newUser($broader, 'otra@dramdd.gob.pe')->assertHasActionErrors(['roles.0']);
        $newUser($covered, 'nueva@dramdd.gob.pe')->assertHasNoActionErrors();

        $this->assertDatabaseMissing('users', ['email' => 'otra@dramdd.gob.pe']);

        $created = User::query()->where('email', 'nueva@dramdd.gob.pe')->firstOrFail();
        $this->assertFalse($created->is_admin);
        $this->assertSame([$covered->id], $created->roles->modelKeys());
    }

    public function test_user_managers_cannot_touch_administrators_or_more_privileged_accounts(): void
    {
        $manager = User::factory()->withPermissions(['usuarios' => ['view', 'create', 'update', 'delete'], 'noticias' => self::ALL])->create();
        $admin = User::factory()->admin()->create();
        $documentsClerk = User::factory()->withPermissions(['documentos' => ['view']])->create();
        $newsReader = User::factory()->withPermissions(['noticias' => ['view']])->create();

        $this->assertFalse($manager->can('update', $admin));
        $this->assertFalse($manager->can('delete', $admin));
        $this->assertFalse($manager->can('update', $documentsClerk));
        $this->assertTrue($manager->can('update', $newsReader));
        $this->assertTrue($manager->can('delete', $newsReader));
        $this->assertFalse($manager->can('viewAny', Role::class));
    }

    public function test_administrators_build_roles_from_the_permission_matrix(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateRole::class)
            ->fillForm([
                'name' => 'Prensa',
                'description' => 'Oficina de imagen institucional',
                'permissions' => ['noticias' => ['create'], 'banners' => ['update']],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $role = Role::query()->where('name', 'Prensa')->firstOrFail();

        $this->assertSame(['noticias' => ['view', 'create'], 'banners' => ['view', 'update']], $role->permissions);
        $this->assertNull($role->document_type_ids);
    }

    public function test_role_assignments_are_written_to_the_audit_log(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $user = User::factory()->create(['email' => 'rosa@dramdd.gob.pe']);
        $role = Role::factory()->create(['name' => 'Recursos humanos']);

        $user->roles()->attach($role);
        $user->roles()->detach($role);

        $this->assertDatabaseHas('activity_logs', ['event' => 'role_assigned', 'subject_label' => 'rosa@dramdd.gob.pe · Recursos humanos']);
        $this->assertDatabaseHas('activity_logs', ['event' => 'role_removed', 'subject_label' => 'rosa@dramdd.gob.pe · Recursos humanos']);
        $this->assertSame(2, ActivityLog::query()->whereIn('event', ['role_assigned', 'role_removed'])->count());
    }
}
