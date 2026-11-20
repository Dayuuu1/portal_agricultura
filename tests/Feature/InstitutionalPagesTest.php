<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Models\Document;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InstitutionalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_long_pages_get_an_index_downloads_and_related_links(): void
    {
        $approved = Document::factory()->withFile('documentos/2026/codigo-etica.pdf')->create(['title' => 'Código de ética aprobado por RD N° 120-2026']);
        $draft = Document::factory()->unpublished()->create(['title' => 'Borrador del código de ética']);
        $page = Page::factory()->create([
            'title' => 'Código de Ética de la Función Pública',
            'body' => '<p>Ley N° 27815.</p><h2>Principios</h2><p>Respeto.</p><h2>Deberes</h2><p>Neutralidad.</p><h2>Prohibiciones</h2><p>…</p>',
            'show_in_menu' => true,
        ]);
        $page->documents()->attach([$approved->id, $draft->id]);
        Page::factory()->create(['title' => 'Plan de integridad', 'show_in_menu' => true]);

        $this->get(route('pages.show', $page))
            ->assertOk()
            ->assertSeeInOrder(['En esta página', 'href="#principios"', 'href="#deberes"', 'href="#prohibiciones"'], false)
            ->assertSee('<h2 id="deberes">Deberes</h2>', false)
            ->assertSee(['Documentos para descargar', 'Código de ética aprobado por RD N° 120-2026'])
            ->assertDontSee('Borrador del código de ética')
            ->assertSeeInOrder(['Información institucional', 'Nosotros', 'Organigrama', 'Directorio', 'Plan de integridad'])
            ->assertSee('¿Tiene alguna consulta?');
    }

    public function test_short_pages_skip_the_index_and_the_downloads(): void
    {
        $page = Page::factory()->create(['body' => '<p>Ley N° 29733.</p><h2>Finalidad</h2><p>Atender su solicitud.</p>']);

        $this->get(route('pages.show', $page))
            ->assertOk()
            ->assertDontSee('En esta página')
            ->assertDontSee('Documentos para descargar');
    }

    public function test_pages_chosen_in_the_panel_appear_in_the_institutional_menu(): void
    {
        Page::factory()->create(['title' => 'Plan de integridad institucional', 'show_in_menu' => true]);
        Page::factory()->create(['title' => 'Página interna de pruebas', 'show_in_menu' => false]);
        Page::factory()->create(['title' => 'Política anticorrupción en revisión', 'show_in_menu' => true, 'is_published' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Plan de integridad institucional')
            ->assertDontSee('Página interna de pruebas')
            ->assertDontSee('Política anticorrupción en revisión');
    }

    public function test_the_about_text_leads_to_its_own_page(): void
    {
        Page::factory()->create(['slug' => 'nosotros', 'title' => 'Nosotros']);

        $this->get('/p/nosotros')->assertRedirect(route('institutional.about'))->assertStatus(301);
    }

    public function test_editors_choose_the_menu_and_the_documents_of_a_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $document = Document::factory()->create(['title' => 'Plan de integridad 2026-2028']);

        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Plan de integridad',
                'excerpt' => 'Medidas de integridad y lucha contra la corrupción.',
                'body' => '<p>Texto del plan.</p>',
                'show_in_menu' => true,
                'documents' => [$document->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::query()->where('slug', 'plan-de-integridad')->sole();
        $this->assertTrue($page->show_in_menu);
        $this->assertSame([$document->id], $page->documents()->pluck('documents.id')->all());
    }
}
