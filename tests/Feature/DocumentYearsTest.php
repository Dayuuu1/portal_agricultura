<?php

namespace Tests\Feature;

use App\Enums\DocumentSection;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentYearsTest extends TestCase
{
    use RefreshDatabase;

    private function livestockType(): DocumentType
    {
        return DocumentType::factory()->section(DocumentSection::InformacionAgraria)->create([
            'name' => 'Estadística Pecuaria',
            'group_by_year' => true,
        ]);
    }

    public function test_reference_year_comes_from_the_title_and_falls_back_to_the_issue_date(): void
    {
        $fromTitle = Document::factory()->create(['title' => 'Producción pecuaria – Diciembre 2020', 'issued_at' => '2021-01-20']);
        $fromDate = Document::factory()->create(['title' => 'Boletín trimestral', 'issued_at' => '2024-05-10']);

        $this->assertSame(2020, $fromTitle->period_year);
        $this->assertSame(2024, $fromDate->period_year);
        $this->assertSame('Producción pecuaria octubre 2026', Document::titleFromFileName('PRODUCCION_PECUARIA-OCTUBRE-2026.pdf'));
    }

    public function test_types_organized_by_year_open_on_the_latest_year_with_year_buttons(): void
    {
        $type = $this->livestockType();
        $recent = Document::factory()->for($type)->create(['title' => 'Beneficio de ganado octubre 2026']);
        $older = Document::factory()->for($type)->create(['title' => 'Beneficio de ganado diciembre 2019']);

        $this->get($type->publicUrl())
            ->assertOk()
            ->assertSee(route('documents.show', $recent))
            ->assertDontSee(route('documents.show', $older))
            ->assertSee('2019')
            ->assertSee('del año');

        $this->get($type->publicUrl(['anio' => 2019]))
            ->assertSee(route('documents.show', $older))
            ->assertDontSee(route('documents.show', $recent));

        $this->get($type->publicUrl(['anio' => 'todos']))
            ->assertSee($recent->title)
            ->assertSee($older->title);
    }

    public function test_types_not_organized_by_year_show_everything(): void
    {
        $type = DocumentType::factory()->create(['group_by_year' => false]);
        $documents = Document::factory()->for($type)->count(2)->sequence(['period_year' => 2020], ['period_year' => 2025])->create();

        $this->get('/documentos?tipo='.$type->slug)
            ->assertSee($documents[0]->title)
            ->assertSee($documents[1]->title);
    }

    public function test_several_files_can_be_uploaded_at_once(): void
    {
        Storage::fake('public');
        $type = $this->livestockType();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListDocuments::class)
            ->callAction('bulkUpload', data: [
                'document_type_id' => $type->id,
                'period_year' => 2026,
                'issued_at' => '2026-11-15',
                'files' => [
                    UploadedFile::fake()->create('Produccion pecuaria octubre 2026.pdf', 20, 'application/pdf'),
                    UploadedFile::fake()->create('Beneficio_de_ganado_octubre_2026.pdf', 30, 'application/pdf'),
                ],
                'is_published' => true,
            ])
            ->assertHasNoActionErrors();

        $documents = Document::query()->whereBelongsTo($type)->orderBy('title')->get();

        $this->assertCount(2, $documents);
        $this->assertSame(['Beneficio de ganado octubre 2026', 'Produccion pecuaria octubre 2026'], $documents->pluck('title')->all());
        $this->assertTrue($documents->every(fn (Document $document): bool => $document->period_year === 2026 && $document->is_published));
        $documents->each(fn (Document $document) => Storage::disk('public')->assertExists($document->file_path));
    }

    public function test_upload_form_preselects_the_type_from_the_link(): void
    {
        $type = $this->livestockType();
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/documentos/create?tipo='.$type->slug)->assertOk();

        Livewire::withQueryParams(['tipo' => $type->slug])
            ->test(CreateDocument::class)
            ->assertSchemaStateSet(['document_type_id' => $type->id]);
    }
}
