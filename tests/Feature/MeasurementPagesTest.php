<?php

namespace Tests\Feature;

use App\Models\Medicion;
use App\Models\Sensor;
use App\Models\TipoSensor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_filter_measurements_and_open_a_detail(): void
    {
        $temperature = TipoSensor::factory()->create(['nombre' => 'Temperatura', 'slug' => 'temperatura']);
        $sensor = Sensor::factory()->create(['tipo_sensor_id' => $temperature->getKey()]);
        $measurement = Medicion::factory()->create([
            'sensor_id' => $sensor->getKey(),
            'measurement_id' => 'MEDICION-FILTRO-001',
            'origen' => 'esp32',
            'valor' => 31.2,
            'unidad' => 'cm',
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/mediciones?origen=esp32&q=MEDICION-FILTRO')
            ->assertOk()
            ->assertSeeText('Mediciones')
            ->assertSeeText('MEDICION-FILTRO-001')
            ->assertSeeText('Temperatura');

        $this->get(route('mediciones.show', $measurement))
            ->assertOk()
            ->assertSeeText('MEDICION-FILTRO-001')
            ->assertSeeText('31.20 cm');
    }

    public function test_guest_is_redirected_from_measurement_list(): void
    {
        $this->get('/mediciones')->assertRedirect('/login');
    }

    public function test_dashboard_shows_temperature_chart_without_mixing_demo_and_real_sources(): void
    {
        $temperature = TipoSensor::factory()->create(['nombre' => 'Temperatura', 'slug' => 'temperatura']);
        $sensor = Sensor::factory()->demo()->create(['tipo_sensor_id' => $temperature->getKey()]);
        Medicion::factory()->create(['sensor_id' => $sensor->getKey(), 'valor' => 27.5]);
        Medicion::factory()->create(['sensor_id' => $sensor->getKey(), 'valor' => 29.0]);

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Serie DEMO · temperatura')
            ->assertSeeText('La serie muestra una sola fuente a la vez');
    }
}