<?php

namespace Tests\Feature;

use App\Exceptions\MeasurementIdConflictException;
use App\Models\Alerta;
use App\Models\Medicion;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Sensor;
use App\Models\User;
use App\Services\MeasurementIngestionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensor_measurement_alert_and_role_permission_relations_work(): void
    {
        $sensor = Sensor::factory()->create();
        $measurement = Medicion::factory()->create(['sensor_id' => $sensor->getKey()]);
        $alert = Alerta::factory()->create([
            'sensor_id' => $sensor->getKey(),
            'zona_id' => $sensor->zona_id,
        ]);

        $role = Role::query()->create([
            'name' => 'Operador de prueba', 'slug' => 'operador-prueba',
            'description' => 'Rol de prueba.', 'status' => 'activo',
        ]);
        $permission = Permission::query()->create([
            'name' => 'Ver sensores', 'slug' => 'sensores.ver', 'description' => 'Consulta sensores.',
        ]);
        $role->permissions()->attach($permission->getKey());
        $user = User::factory()->create();
        $user->roles()->attach($role->getKey());

        $this->assertSame($sensor->zona_id, $sensor->zona->getKey());
        $this->assertNotNull($sensor->tipoSensor);
        $this->assertSame($sensor->getKey(), $measurement->sensor->getKey());
        $this->assertSame($sensor->getKey(), $alert->sensor->getKey());
        $this->assertTrue($user->hasRole('operador-prueba'));
        $this->assertTrue($user->hasPermission('sensores.ver'));
        $this->assertSame($permission->getKey(), $role->permissions()->firstOrFail()->getKey());
    }

    public function test_measurement_ingestion_is_idempotent_and_detects_cross_sensor_id_collision(): void
    {
        $sensor = Sensor::factory()->create(['codigo' => 'SEN-IDEMPOTENCIA-1']);
        $otherSensor = Sensor::factory()->create(['codigo' => 'SEN-IDEMPOTENCIA-2']);
        $service = app(MeasurementIngestionService::class);
        $payload = [
            'sensor_code' => $sensor->codigo,
            'measurement_id' => 'ESP32-SEN001-000001',
            'value' => 72.5,
            'unit' => '%',
            'measured_at' => now()->toIso8601String(),
            'quality' => 'good',
        ];

        $first = $service->ingest($payload);
        $second = $service->ingest($payload);

        $this->assertTrue($first['created']);
        $this->assertSame('created', $first['status']);
        $this->assertFalse($second['created']);
        $this->assertSame('duplicate', $second['status']);
        $this->assertSame($first['measurement']->getKey(), $second['measurement']->getKey());
        $this->assertDatabaseCount('mediciones', 1);

        $payload['sensor_code'] = $otherSensor->codigo;
        $this->expectException(MeasurementIdConflictException::class);
        $service->ingest($payload);
    }

    public function test_database_enforces_unique_measurement_id(): void
    {
        $sensor = Sensor::factory()->create();
        Medicion::factory()->create([
            'sensor_id' => $sensor->getKey(),
            'measurement_id' => 'UNICO-EN-BASE-DE-DATOS',
        ]);

        try {
            Medicion::factory()->create([
                'sensor_id' => $sensor->getKey(),
                'measurement_id' => 'UNICO-EN-BASE-DE-DATOS',
            ]);
            $this->fail('La base de datos aceptó un measurement_id duplicado.');
        } catch (QueryException) {
            $this->assertDatabaseCount('mediciones', 1);
        }
    }

    public function test_dashboard_labels_demo_records_and_excludes_them_from_real_totals(): void
    {
        $user = User::factory()->create();
        $sensor = Sensor::factory()->demo()->create();
        Medicion::factory()->create(['sensor_id' => $sensor->getKey()]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Datos DEMO')
            ->assertSeeText('1 mediciones DEMO')
            ->assertSeeText('no son telemetría real');
    }

    public function test_permission_seeder_assigns_role_capabilities(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $administrator = Role::query()->where('slug', 'administrador')->firstOrFail();
        $operator = Role::query()->where('slug', 'operador')->firstOrFail();
        $viewer = Role::query()->where('slug', 'consulta')->firstOrFail();

        $this->assertSame(Permission::query()->count(), $administrator->permissions()->count());
        $this->assertTrue($operator->permissions()->where('slug', 'alertas.gestionar')->exists());
        $this->assertFalse($viewer->permissions()->where('slug', 'alertas.gestionar')->exists());
        $this->assertTrue($viewer->permissions()->where('slug', 'mediciones.ver')->exists());
    }
}
