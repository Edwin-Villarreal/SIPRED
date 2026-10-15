<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_modules_open_for_authenticated_users_and_render_their_data_pages(): void
    {
        $this->actingAs(User::factory()->create());

        $destinations = [
            '/mapa-riesgos' => 'Mapa de riesgos',
            '/sensores' => 'Sensores',
            '/mediciones' => 'Mediciones',
            '/alertas' => 'Alertas',
            '/eventos' => 'Eventos',
            '/reportes' => 'Reportes',
            '/usuarios' => 'Usuarios',
            '/configuracion' => 'Configuración',
        ];

        foreach ($destinations as $path => $heading) {
            $this->get($path)
                ->assertOk()
                ->assertSee($heading);
        }

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee(route('riesgos.index'), false)
            ->assertSee(route('sensores.index'), false)
            ->assertSee(route('alertas.index'), false)
            ->assertSee(route('eventos.index'), false)
            ->assertSee(route('reportes.index'), false)
            ->assertSee(route('usuarios.index'), false)
            ->assertSee(route('configuracion.index'), false);
    }

    public function test_sensor_list_search_returns_matching_sensor(): void
    {
        $this->actingAs(User::factory()->create());
        $zone = \App\Models\Zona::factory()->create();
        $sensor = \App\Models\Sensor::factory()->create([
            'zona_id' => $zone->getKey(),
            'codigo' => 'SEN-BUSQUEDA-01',
        ]);

        $this->get('/sensores?q=SEN-BUSQUEDA-01')
            ->assertOk()
            ->assertSee('SEN-BUSQUEDA-01')
            ->assertSee(route('mediciones.index', ['sensor_id' => $sensor->getKey()]), false);
    }
}