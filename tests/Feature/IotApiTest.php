<?php

namespace Tests\Feature;

use App\Models\Sensor;
use App\Models\SensorToken;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class IotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_measurement_is_saved_with_event_and_sensor_timestamps(): void
    {
        $sensor = Sensor::factory()->create();
        $plainToken = $this->makeToken($sensor);

        $this->postJson('/api/iot/measurements', $this->measurementPayload($sensor), $this->bearer($plainToken))
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sensor_code', $sensor->codigo)
            ->assertJsonPath('data.origin', 'esp32');

        $this->assertDatabaseCount('mediciones', 1);
        $this->assertDatabaseHas('eventos', ['sensor_id' => $sensor->getKey(), 'tipo' => 'medicion']);
        $this->assertNotNull($sensor->fresh()->ultima_medicion_at);
        $this->assertNotNull($sensor->fresh()->ultima_conexion_at);
        $this->assertNotNull(SensorToken::query()->firstOrFail()->ultimo_uso_at);
    }

    public function test_invalid_measurement_returns_422_without_writing_data(): void
    {
        $sensor = Sensor::factory()->create();
        $plainToken = $this->makeToken($sensor);
        $payload = $this->measurementPayload($sensor);
        $payload['quality'] = 'broken';

        $this->postJson('/api/iot/measurements', $payload, $this->bearer($plainToken))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quality');

        $this->assertDatabaseCount('mediciones', 0);
    }

    public function test_unknown_sensor_returns_404_and_invalid_token_returns_401(): void
    {
        $sensor = Sensor::factory()->create();

        $this->postJson('/api/iot/measurements', [
            'sensor_code' => 'SEN-NO-EXISTE',
            'measurement_id' => 'UNKNOWN-001',
            'value' => 1,
            'unit' => 'cm',
            'measured_at' => now()->toISOString(),
        ], $this->bearer('invalid-token'))->assertNotFound();

        $this->postJson('/api/iot/measurements', $this->measurementPayload($sensor), $this->bearer('invalid-token'))
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_duplicate_measurement_is_idempotent_and_collision_is_rejected(): void
    {
        $sensor = Sensor::factory()->create();
        $otherSensor = Sensor::factory()->create();
        $plainToken = $this->makeToken($sensor);
        $otherToken = $this->makeToken($otherSensor);
        $payload = $this->measurementPayload($sensor, 'TEST-001');

        $this->postJson('/api/iot/measurements', $payload, $this->bearer($plainToken))->assertCreated();
        $this->postJson('/api/iot/measurements', $payload, $this->bearer($plainToken))
            ->assertOk()
            ->assertJsonPath('message', 'Medición ya registrada.');

        $this->assertDatabaseCount('mediciones', 1);
        $this->assertDatabaseCount('eventos', 1);

        $payload['sensor_code'] = $otherSensor->codigo;
        $this->postJson('/api/iot/measurements', $payload, $this->bearer($otherToken))
            ->assertStatus(409);
        $this->assertDatabaseCount('mediciones', 1);
    }

    public function test_inactive_sensor_cannot_ingest_measurements(): void
    {
        $sensor = Sensor::factory()->create(['estado' => Sensor::STATUS_INACTIVE]);
        $plainToken = $this->makeToken($sensor);

        $this->postJson('/api/iot/measurements', $this->measurementPayload($sensor), $this->bearer($plainToken))
            ->assertStatus(409);

        $this->assertDatabaseCount('mediciones', 0);
    }

    public function test_heartbeat_updates_connectivity_and_status_endpoint(): void
    {
        $sensor = Sensor::factory()->create();
        $plainToken = $this->makeToken($sensor);

        $this->postJson('/api/iot/heartbeat', [
            'sensor_code' => $sensor->codigo,
            'device_id' => 'ESP32-TEST',
            'firmware_version' => '0.1.0',
            'signal_strength' => -67,
            'battery_level' => 88,
            'status' => 'online',
        ], $this->bearer($plainToken))
            ->assertCreated()
            ->assertJsonPath('data.status', 'online');

        $this->assertDatabaseCount('sensor_heartbeats', 1);
        $this->assertDatabaseHas('eventos', ['sensor_id' => $sensor->getKey(), 'tipo' => 'conexion']);

        $this->getJson('/api/iot/sensors/'.$sensor->codigo.'/status', $this->bearer($plainToken))
            ->assertOk()
            ->assertJsonPath('data.sensor_code', $sensor->codigo)
            ->assertJsonPath('data.status', Sensor::STATUS_ACTIVE)
            ->assertJsonPath('data.connectivity', 'online');

        $this->travel(301)->seconds();

        $this->getJson('/api/iot/sensors/'.$sensor->codigo.'/status', $this->bearer($plainToken))
            ->assertOk()
            ->assertJsonPath('data.connectivity', 'offline');
    }

    public function test_repeated_heartbeat_records_history_without_repeating_state_event(): void
    {
        $sensor = Sensor::factory()->create();
        $plainToken = $this->makeToken($sensor);
        $payload = [
            'sensor_code' => $sensor->codigo,
            'status' => 'online',
            'signal_strength' => -55,
        ];

        $this->postJson('/api/iot/heartbeat', $payload, $this->bearer($plainToken))->assertCreated();
        $this->postJson('/api/iot/heartbeat', $payload, $this->bearer($plainToken))->assertCreated();

        $this->assertDatabaseCount('sensor_heartbeats', 2);
        $this->assertDatabaseCount('eventos', 1);
    }

    public function test_simulator_sends_measurements_through_the_same_api_and_revokes_its_temporary_token(): void
    {
        $zona = Zona::factory()->create(['is_demo' => true]);
        $sensor = Sensor::factory()->demo()->create([
            'codigo' => 'SEN-SIMULADOR',
            'zona_id' => $zona->getKey(),
        ]);
        $requests = [];

        Http::fake(function (ClientRequest $request) use (&$requests) {
            $requests[] = $request;
            $path = parse_url($request->url(), PHP_URL_PATH);

            return Http::response([
                'success' => true,
                'message' => $path === '/api/iot/measurements'
                    ? 'Medición registrada correctamente.'
                    : 'Heartbeat registrado correctamente.',
                'data' => ['measurement_id' => $request->data()['measurement_id'] ?? null],
            ], 201);
        });

        $this->artisan('sipred:simulate-sensor', [
            'sensor_code' => $sensor->codigo,
            '--count' => 3,
        ])->expectsOutputToContain('Se enviaron 3 medición(es)')
            ->assertSuccessful();

        $measurementRequests = array_values(array_filter(
            $requests,
            fn (ClientRequest $request): bool => parse_url($request->url(), PHP_URL_PATH) === '/api/iot/measurements'
        ));
        $heartbeatRequests = array_values(array_filter(
            $requests,
            fn (ClientRequest $request): bool => parse_url($request->url(), PHP_URL_PATH) === '/api/iot/heartbeat'
        ));

        $this->assertCount(4, $requests);
        $this->assertCount(3, $measurementRequests);
        $this->assertCount(1, $heartbeatRequests);
        $this->assertCount(3, array_unique(array_map(
            fn (ClientRequest $request): string => $request->data()['measurement_id'],
            $measurementRequests
        )));
        foreach ($requests as $request) {
            $authorization = $request->header('Authorization');
            $authorization = is_array($authorization) ? ($authorization[0] ?? '') : $authorization;
            $this->assertStringStartsWith('Bearer ', (string) $authorization);
            $this->assertSame($sensor->codigo, $request->data()['sensor_code']);
        }

        $this->assertDatabaseCount('mediciones', 0);
        $this->assertDatabaseHas('sensor_tokens', ['sensor_id' => $sensor->getKey(), 'estado' => 'revocado']);
    }
    private function makeToken(Sensor $sensor): string
    {
        $plainText = Str::random(64);
        $sensor->tokens()->create([
            'token_hash' => SensorToken::hashPlainTextToken($plainText),
            'nombre' => 'Token de prueba',
            'estado' => SensorToken::STATUS_ACTIVE,
        ]);

        return $plainText;
    }

    /** @return array<string, mixed> */
    private function measurementPayload(Sensor $sensor, ?string $measurementId = null): array
    {
        return [
            'sensor_code' => $sensor->codigo,
            'measurement_id' => $measurementId ?? 'TEST-'.Str::upper(Str::random(12)),
            'value' => 72.5,
            'unit' => 'cm',
            'measured_at' => now()->toISOString(),
            'quality' => 'good',
        ];
    }

    /** @return array<string, string> */
    private function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_status_endpoint_rejects_a_token_for_a_different_sensor_even_when_query_code_matches(): void
    {
        $pathSensor = Sensor::factory()->create();
        $tokenSensor = Sensor::factory()->create();
        $plainToken = $this->makeToken($tokenSensor);

        $this->getJson(
            '/api/iot/sensors/'.$pathSensor->codigo.'/status?sensor_code='.$tokenSensor->codigo,
            $this->bearer($plainToken),
        )->assertUnauthorized();
    }

    public function test_unexpected_api_errors_return_a_generic_message(): void
    {
        \Illuminate\Support\Facades\Route::get('/api/iot/intentional-error', function () {
            throw new \RuntimeException('private database detail');
        });

        $this->getJson('/api/iot/intentional-error')
            ->assertStatus(500)
            ->assertJsonPath('message', 'Error interno del servidor.')
            ->assertDontSee('private database detail');
    }
}