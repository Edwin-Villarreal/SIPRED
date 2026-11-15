<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Models\Evento;
use App\Models\Medicion;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Sensor;
use App\Models\TipoSensor;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_zone_endpoints_support_read_create_update_and_safe_retirement(): void
    {
        $user = $this->userWithPermissions('zonas.ver', 'zonas.crear', 'zonas.editar', 'zonas.eliminar');
        $this->actingAs($user);

        $payload = [
            'nombre' => 'Ribera norte',
            'slug' => 'ribera-norte',
            'nivel_riesgo' => 'alto',
            'estado' => 'activa',
            'municipio' => 'El Espinal',
            'departamento' => 'Tolima',
            'pais' => 'Colombia',
            'latitud' => 4.1486,
            'longitud' => -74.8878,
            'is_demo' => false,
        ];

        $created = $this->postJson('/api/gestion/zonas', $payload)
            ->assertCreated()
            ->assertJsonPath('data.slug', 'ribera-norte');
        $zoneId = $created->json('data.id');

        $this->getJson('/api/gestion/zonas')->assertOk()->assertJsonPath('data.0.nombre', 'Ribera norte');
        $this->getJson('/api/gestion/zonas/'.$zoneId)->assertOk()->assertJsonPath('data.nivel_riesgo', 'alto');
        $this->patchJson('/api/gestion/zonas/'.$zoneId, ['nivel_riesgo' => 'critico'])
            ->assertOk()->assertJsonPath('data.nivel_riesgo', 'critico');
        $this->deleteJson('/api/gestion/zonas/'.$zoneId)->assertOk();
        $this->assertSoftDeleted('zonas', ['id' => $zoneId]);

        $occupiedZone = Zona::factory()->create();
        Sensor::factory()->create(['zona_id' => $occupiedZone->getKey()]);
        $this->deleteJson('/api/gestion/zonas/'.$occupiedZone->getKey())
            ->assertConflict()->assertJsonPath('message', 'No se puede retirar la zona mientras tenga sensores asociados.');
    }

    public function test_sensor_endpoints_validate_schema_and_keep_telemetry_fields_server_managed(): void
    {
        $user = $this->userWithPermissions('sensores.ver', 'sensores.crear', 'sensores.editar', 'sensores.eliminar');
        $this->actingAs($user);
        $template = Sensor::factory()->create();

        $payload = [
            'tipo_sensor_id' => $template->tipo_sensor_id,
            'zona_id' => $template->zona_id,
            'codigo' => 'SEN-CRUD-101',
            'nombre' => 'Sensor de nivel',
            'unidad_medida' => 'cm',
            'estado' => Sensor::STATUS_ACTIVE,
            'intervalo_lectura' => 30,
            'ultima_medicion_at' => now()->toISOString(),
            'ultima_conexion_at' => now()->toISOString(),
        ];

        $created = $this->postJson('/api/gestion/sensores', $payload)
            ->assertCreated()
            ->assertJsonPath('data.codigo', 'SEN-CRUD-101');
        $sensorId = $created->json('data.id');
        $this->assertNull(Sensor::query()->findOrFail($sensorId)->ultima_medicion_at);

        $this->getJson('/api/gestion/sensores')->assertOk()->assertJsonFragment(['codigo' => 'SEN-CRUD-101']);
        $this->getJson('/api/gestion/tipos-sensores')->assertOk()->assertJsonFragment(['id' => $template->tipo_sensor_id]);
        $this->getJson('/api/gestion/sensores/'.$sensorId)->assertOk()->assertJsonPath('data.zona.id', $template->zona_id);
        $this->patchJson('/api/gestion/sensores/'.$sensorId, ['nombre' => 'Sensor actualizado'])
            ->assertOk()->assertJsonPath('data.nombre', 'Sensor actualizado');
        $this->deleteJson('/api/gestion/sensores/'.$sensorId)->assertOk();
        $this->assertSoftDeleted('sensores', ['id' => $sensorId]);
    }

    public function test_validation_permissions_and_historical_endpoints_are_enforced(): void
    {
        $viewer = $this->userWithPermissions('zonas.ver', 'zonas.crear', 'sensores.ver', 'mediciones.ver', 'eventos.ver');
        $this->actingAs($viewer);

        $this->postJson('/api/gestion/zonas', ['nombre' => 'Zona sin slug'])->assertUnprocessable()->assertJsonValidationErrors(['slug']);
        $this->postJson('/api/gestion/sensores', [])->assertForbidden();
        $this->postJson('/api/gestion/mediciones', [])->assertMethodNotAllowed();
        $this->deleteJson('/api/gestion/eventos/1')->assertMethodNotAllowed();

        $sensor = Sensor::factory()->create();
        $this->get('/sensores/crear')->assertForbidden();
        $this->get('/sensores/'.$sensor->getKey().'/editar')->assertForbidden();

        $measurement = Medicion::factory()->create(['sensor_id' => $sensor->getKey()]);
        $event = Evento::factory()->create(['sensor_id' => $sensor->getKey()]);
        $this->getJson('/api/gestion/mediciones')->assertOk()->assertJsonFragment(['measurement_id' => $measurement->measurement_id]);
        $this->getJson('/api/gestion/mediciones/'.$measurement->getKey())->assertOk();
        $this->getJson('/api/gestion/eventos')->assertOk()->assertJsonFragment(['titulo' => $event->titulo]);
        $this->getJson('/api/gestion/eventos/'.$event->getKey())->assertOk();
    }

    public function test_alert_status_changes_create_an_event_and_require_management_permission(): void
    {
        $operator = $this->userWithPermissions('alertas.ver', 'alertas.gestionar', 'eventos.ver');
        $this->actingAs($operator);
        $sensor = Sensor::factory()->create();
        $alert = Alerta::factory()->create([
            'sensor_id' => $sensor->getKey(),
            'zona_id' => $sensor->zona_id,
            'estado' => Alerta::STATUS_ACTIVE,
        ]);

        $this->getJson('/api/gestion/alertas')->assertOk();
        $this->getJson('/api/gestion/alertas/'.$alert->getKey())->assertOk();
        $this->patchJson('/api/gestion/alertas/'.$alert->getKey(), ['estado' => Alerta::STATUS_ACKNOWLEDGED])
            ->assertOk()->assertJsonPath('data.estado', Alerta::STATUS_ACKNOWLEDGED);
        $this->assertDatabaseHas('eventos', [
            'alerta_id' => $alert->getKey(),
            'usuario_id' => $operator->getKey(),
            'tipo' => 'alerta',
            'origen' => 'usuario',
        ]);

        $viewer = $this->userWithPermissions('alertas.ver');
        $this->actingAs($viewer)
            ->patchJson('/api/gestion/alertas/'.$alert->getKey(), ['estado' => Alerta::STATUS_RESOLVED])
            ->assertForbidden();
    }

    public function test_administrator_management_pages_render_forms_and_actions(): void
    {
        $admin = $this->userWithPermissions(
            'zonas.crear', 'zonas.editar', 'zonas.eliminar',
            'sensores.crear', 'sensores.editar', 'sensores.eliminar',
        );
        $this->actingAs($admin);
        $zone = Zona::factory()->create();
        $sensor = Sensor::factory()->create(['zona_id' => $zone->getKey()]);

        $this->get('/mapa-riesgos')->assertOk()->assertSee('Registrar zona')->assertSee(route('riesgos.edit', $zone));
        $this->get('/mapa-riesgos/crear')->assertOk()->assertSee('name="nivel_riesgo"', false);
        $this->get(route('riesgos.edit', $zone))->assertOk()->assertSee('name="slug"', false);
        $this->get('/sensores')->assertOk()->assertSee('Registrar sensor')->assertSee(route('sensores.edit', $sensor));
        $this->get('/sensores/crear')->assertOk()->assertSee('name="tipo_sensor_id"', false);
        $this->get(route('sensores.edit', $sensor))->assertOk()->assertSee('name="codigo"', false);
    }

    public function test_csrf_bootstrap_returns_the_current_web_session_token(): void
    {
        $this->getJson('/api/csrf-cookie')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['csrf_token']);
    }

    public function test_vite_origin_receives_cors_permission_for_session_credentials(): void
    {
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'accept,content-type',
        ])->options('/api/gestion/sensores')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    private function userWithPermissions(string ...$slugs): User
    {
        $unique = uniqid();
        $role = Role::query()->create(['name' => 'Prueba CRUD '.$unique, 'slug' => 'prueba-crud-'.$unique, 'status' => 'activo']);

        foreach ($slugs as $slug) {
            $permission = Permission::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'description' => 'Permiso de prueba.'],
            );
            $role->permissions()->attach($permission->getKey());
        }

        $user = User::factory()->create();
        $user->roles()->attach($role->getKey());

        return $user;
    }
}
