<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
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

    public function test_configuration_shows_recovery_options_and_only_authorized_admins_get_switches(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/configuracion')
            ->assertOk()
            ->assertSeeText('Recuperación de cuenta')
            ->assertSeeText('Generar y descargar códigos')
            ->assertSeeText('Generar y descargar archivo')
            ->assertSee('action="'.route('account.recovery.codes').'"', false)
            ->assertSee('action="'.route('account.recovery.file').'"', false)
            ->assertSeeText('0 disponibles')
            ->assertSeeText('Sin archivo vigente')
            ->assertSeeText('Permite activar o desactivar la recuperación mediante códigos de respaldo.')
            ->assertSeeText('Permite activar o desactivar la recuperación mediante archivo de respaldo.')
            ->assertSeeText('Permite establecer una contraseña nueva después de verificar correctamente la identidad del usuario.')
            ->assertSeeText('Activado')
            ->assertDontSee('role="switch"', false)
            ->assertDontSee(route('recovery.settings.update'), false);

        $admin = User::factory()->create();
        $permission = Permission::query()->create([
            'name' => 'Configurar recuperación',
            'slug' => 'recovery.settings',
            'description' => 'Administrar métodos de recuperación.',
        ]);
        $role = Role::query()->create([
            'name' => 'Administrador de prueba',
            'slug' => 'administrador-prueba',
            'description' => 'Rol de prueba.',
            'status' => 'activo',
        ]);
        $role->permissions()->attach($permission->getKey());
        $admin->roles()->attach($role->getKey());

        $this->actingAs($admin)
            ->get('/configuracion')
            ->assertOk()
            ->assertSee('Generar y descargar códigos')
            ->assertSee('Generar y descargar archivo')
            ->assertSee('href="#recovery-settings-section"', false)
            ->assertSee('role="switch"', false)
            ->assertSee('name="recovery_codes_enabled"', false)
            ->assertSee('name="recovery_file_enabled"', false)
            ->assertDontSee('name="password_reset_enabled"', false);

        $this->get(route('recovery.settings.edit'))
            ->assertOk()
            ->assertSeeText('Recuperación de cuenta')
            ->assertSeeText('Generar y descargar códigos')
            ->assertSeeText('Generar y descargar archivo')
            ->assertSee('action="'.route('account.recovery.codes').'"', false)
            ->assertSee('action="'.route('account.recovery.file').'"', false)
            ->assertSeeText('0 disponibles')
            ->assertSeeText('Sin archivo vigente')
            ->assertSee('role="switch"', false);
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