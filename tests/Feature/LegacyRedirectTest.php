<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixed_wordpress_urls_redirect_permanently(): void
    {
        $this->get('/contactenos/')->assertRedirect('/contacto')->assertStatus(301);
        $this->get('/category/noticias')->assertRedirect('/noticias')->assertStatus(301);
        $this->get('/organigrama')->assertRedirect('/institucional/organigrama')->assertStatus(301);
    }

    public function test_old_unit_pages_redirect_to_the_matching_unit(): void
    {
        $exact = OrganizationalUnit::factory()->create(['name' => 'Oficina de Control Institucional']);
        $partial = OrganizationalUnit::factory()->create(['name' => 'Oficina de Planeamiento y Presupuesto']);

        $this->get('/oficina-de-control-institucional/')
            ->assertStatus(301)
            ->assertRedirect(route('institutional.unit', $exact));

        $this->get('/planeamiento-y-presupuesto/')
            ->assertStatus(301)
            ->assertRedirect(route('institutional.unit', $partial));
    }

    public function test_old_agency_pages_redirect(): void
    {
        $agency = Agency::factory()->create(['name' => 'Agencia Agraria Tambopata']);

        $this->get('/agencia-agraria-tambopata/')->assertRedirect(route('agencies.show', $agency));
    }

    public function test_unknown_paths_return_not_found(): void
    {
        $this->get('/no-existe')->assertNotFound()->assertSee('No encontramos esta página');
        $this->get('/wp-login.php')->assertNotFound();
        $this->get('/a/b/c')->assertNotFound();
    }
}
