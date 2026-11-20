<?php

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_download_returns_the_file_with_its_original_name_and_counts_it(): void
    {
        $path = UploadedFile::fake()->create('abc123.pdf', 120, 'application/pdf')->store('documentos', 'public');
        $document = Document::factory()->create(['file_path' => $path, 'file_name' => 'TUPA 2026.pdf']);

        $this->get(route('documents.download', $document))
            ->assertOk()
            ->assertDownload('TUPA 2026.pdf');

        $this->assertSame(1, $document->fresh()->downloads);
    }

    public function test_file_size_is_stored_when_a_file_is_attached(): void
    {
        Storage::disk('public')->put('documentos/rof.pdf', str_repeat('a', 2048));

        $document = Document::factory()->create(['file_path' => 'documentos/rof.pdf']);

        $this->assertSame(2048, $document->file_size);
        $this->assertSame('2 KB', $document->human_file_size);
    }

    public function test_external_documents_redirect_to_their_url(): void
    {
        $document = Document::factory()->create(['external_url' => 'https://www.gob.pe/documento']);

        $this->get(route('documents.download', $document))->assertRedirect('https://www.gob.pe/documento');
    }

    public function test_documents_without_file_or_unpublished_cannot_be_downloaded(): void
    {
        $withoutFile = Document::factory()->create();
        $unpublished = Document::factory()->withFile()->unpublished()->create();
        $missingFile = Document::factory()->withFile('documentos/no-existe.pdf')->create();

        $this->get(route('documents.download', $withoutFile))->assertNotFound();
        $this->get(route('documents.download', $unpublished))->assertNotFound();
        $this->get(route('documents.download', $missingFile))->assertNotFound();
    }
}
