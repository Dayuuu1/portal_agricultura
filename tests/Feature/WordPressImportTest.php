<?php

namespace Tests\Feature;

use App\Enums\JobPostingStatus;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\JobPosting;
use App\Models\Link;
use App\Models\OrganizationalUnit;
use App\Models\Post;
use App\Models\Redirect;
use Database\Seeders\AgencySeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\OrganizationalUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WordPressImportTest extends TestCase
{
    use RefreshDatabase;

    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        $this->seed([OrganizationalUnitSeeder::class, CategorySeeder::class, DocumentTypeSeeder::class, AgencySeeder::class]);

        $this->workspace = sys_get_temp_dir().'/wp-import-'.uniqid();
        $files = [
            'documentos/delightful-downloads/2025/04/rd-015.pdf' => '%PDF resolucion',
            'documentos/delightful-downloads/2025/05/cas-bases.pdf' => '%PDF bases',
            'documentos/delightful-downloads/2025/05/cas-final.pdf' => '%PDF resultado final',
            'documentos/delightful-downloads/2025/06/produccion.pdf' => '%PDF produccion identica',
            'documentos/delightful-downloads/2025/07/produccion-copia.pdf' => '%PDF produccion identica',
        ];

        foreach ($files as $path => $contents) {
            File::ensureDirectoryExists(dirname("{$this->workspace}/{$path}"));
            File::put("{$this->workspace}/{$path}", $contents);
        }

        File::ensureDirectoryExists("{$this->workspace}/imagenes/2025/03");

        foreach (['foto1', 'foto2'] as $name) {
            $image = imagecreatetruecolor(40, 30);
            imagejpeg($image, "{$this->workspace}/imagenes/2025/03/{$name}.jpg");
        }
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workspace);

        parent::tearDown();
    }

    private function runImport(bool $dryRun = false): void
    {
        $this->artisan('portal:importar-wordpress', [
            'sql' => base_path('tests/Fixtures/wordpress-dump.sql'),
            '--documentos' => $this->workspace,
            '--imagenes' => $this->workspace.'/imagenes',
            '--simular' => $dryRun,
        ])->assertSuccessful();
    }

    public function test_news_are_imported_with_clean_content_images_and_redirects(): void
    {
        $this->runImport();

        $post = Post::query()->sole();
        $this->assertSame('GOREMAD entregó semillas de cacao a agricultores de Iberia', $post->title);
        $this->assertSame(57, $post->views);
        $this->assertStringNotContainsString('[vc_', $post->body);
        $this->assertStringContainsString('<p>Los agricultores de Iberia recibieron semillas', $post->body);
        Storage::disk('public')->assertExists($post->cover_image);
        $this->assertCount(1, $post->gallery);
        Storage::disk('public')->assertExists($post->gallery[0]);

        $this->get('/goremad-entrego-semillas/')->assertRedirect(route('posts.show', $post))->assertStatus(301);
    }

    public function test_spam_is_discarded_and_document_announcements_are_not_news(): void
    {
        $this->runImport();

        $this->assertDatabaseMissing('posts', ['wp_id' => 11]);
        $this->assertDatabaseMissing('posts', ['wp_id' => 12]);
        $this->assertDatabaseMissing('posts', ['wp_id' => 13]);
    }

    public function test_downloads_become_classified_documents_with_counters_and_old_links(): void
    {
        $this->runImport();

        $resolution = Document::query()->where('wp_id', 30)->sole();
        $this->assertSame('Resoluciones Directorales', $resolution->documentType->name);
        $this->assertSame(3, $resolution->downloads);
        $this->assertTrue($resolution->is_published);
        Storage::disk('public')->assertExists($resolution->file_path);

        $this->get('/?ddownload=30')->assertRedirect(route('documents.download', $resolution));
        $this->get('/resolucion-directoral-015-2025')->assertRedirect(route('documents.show', $resolution));
        $this->assertDatabaseMissing('documents', ['wp_id' => 35]);
    }

    public function test_identical_files_are_stored_once(): void
    {
        $this->runImport();

        $original = Document::query()->where('wp_id', 33)->sole();
        $copy = Document::query()->where('wp_id', 34)->sole();

        $this->assertSame('Estadística Agrícola', $original->documentType->name);
        $this->assertSame($original->file_path, $copy->file_path);
    }

    public function test_job_posting_files_are_grouped_into_one_posting(): void
    {
        $this->runImport();

        $posting = JobPosting::query()->sole();
        $this->assertSame('CAS N° 001-2025-GOREMAD-GRDE/DRA', $posting->code);
        $this->assertSame(JobPostingStatus::Finalizada, $posting->status);
        $this->assertSame(['Bases CAS 01-2025', 'Resultado final CAS 01-2025'], $posting->files->pluck('label')->all());

        $this->get('/resultado-final-cas-01-2025/')->assertRedirect(route('job-postings.show', $posting));
    }

    public function test_unit_pages_update_the_organizational_units(): void
    {
        $this->runImport();

        $unit = OrganizationalUnit::query()->where('acronym', 'OCI')->sole();
        $this->assertSame('La Oficina de Control Institucional es el órgano encargado de ejecutar el control gubernamental en la entidad.', $unit->description);
        $this->assertStringContainsString('<li>Recibir y atender denuncias.</li>', $unit->functions);
    }

    public function test_running_twice_does_not_duplicate_records_nor_log_activity(): void
    {
        $logsBefore = ActivityLog::query()->count();
        $this->runImport();
        $this->runImport();

        $this->assertSame($logsBefore, ActivityLog::query()->count());
        $this->assertSame(1, Post::query()->count());
        $this->assertSame(3, Document::query()->count());
        $this->assertSame(1, JobPosting::query()->count());
        $this->assertSame(2, JobPosting::query()->sole()->files()->count());
        $this->assertGreaterThan(0, Redirect::query()->count());
    }

    public function test_simulation_does_not_write_anything(): void
    {
        $this->runImport(dryRun: true);

        $this->assertSame(0, Post::query()->count());
        $this->assertSame(0, Document::query()->count());
        $this->assertSame(0, Redirect::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_former_carousel_logos_are_given_to_links_without_one(): void
    {
        File::ensureDirectoryExists("{$this->workspace}/imagenes/2018/07");
        imagejpeg(imagecreatetruecolor(309, 85), "{$this->workspace}/imagenes/2018/07/senasa.jpg");
        imagejpeg(imagecreatetruecolor(309, 85), "{$this->workspace}/imagenes/2018/07/inia.jpg");
        $senasa = Link::factory()->create(['name' => 'SENASA']);
        $inia = Link::factory()->create(['name' => 'INIA', 'logo' => 'enlaces/logo-subido-en-el-panel.png']);

        $this->runImport();

        $this->assertSame('enlaces/senasa.jpg', $senasa->fresh()->logo);
        Storage::disk('public')->assertExists('enlaces/senasa.jpg');
        $this->assertSame('enlaces/logo-subido-en-el-panel.png', $inia->fresh()->logo);
    }

    public function test_missing_sql_file_fails_gracefully(): void
    {
        $this->artisan('portal:importar-wordpress', ['sql' => 'no-existe.sql', '--documentos' => $this->workspace])
            ->assertFailed();
    }
}
