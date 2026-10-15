<?php

namespace App\Console\Commands;

use App\Models\Sensor;
use App\Models\SensorToken;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SimulateSensorCommand extends Command
{
    protected $signature = 'sipred:simulate-sensor {sensor_code} {--count=1} {--interval=0} {--value=}';

    protected $description = 'Envía mediciones de demostración a la API IoT de SIPRED.';

    public function handle(): int
    {
        $sensorCode = trim((string) $this->argument('sensor_code'));
        $sensor = Sensor::query()->with('tipoSensor')->where('codigo', $sensorCode)->first();

        if ($sensor === null) {
            $this->error('No existe el sensor indicado.');

            return self::FAILURE;
        }

        if (! $sensor->is_demo) {
            $this->error('El simulador solo puede enviar lecturas a sensores marcados como DEMO.');

            return self::FAILURE;
        }

        if ($sensor->estado !== Sensor::STATUS_ACTIVE) {
            $this->error('El sensor no está activo.');

            return self::FAILURE;
        }

        $count = filter_var($this->option('count'), FILTER_VALIDATE_INT);
        $interval = filter_var($this->option('interval'), FILTER_VALIDATE_INT);
        $manualValue = $this->option('value');

        if ($count === false || $count < 1 || $count > 100) {
            $this->error('El parámetro --count debe estar entre 1 y 100.');

            return self::FAILURE;
        }

        if ($interval === false || $interval < 0 || $interval > 60) {
            $this->error('El parámetro --interval debe estar entre 0 y 60 segundos.');

            return self::FAILURE;
        }

        if ($manualValue !== null && (! is_numeric($manualValue) || ! is_finite((float) $manualValue))) {
            $this->error('El parámetro --value debe ser numérico.');

            return self::FAILURE;
        }

        $plainTextToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $token = $sensor->tokens()->firstOrNew(['nombre' => 'Simulador local']);
        $token->forceFill([
            'token_hash' => SensorToken::hashPlainTextToken($plainTextToken),
            'estado' => SensorToken::STATUS_ACTIVE,
            'ultimo_uso_at' => null,
            'expira_at' => null,
            'revocado_at' => null,
        ])->save();

        $apiBase = rtrim((string) config('sipred.iot_api_url', 'http://127.0.0.1:8000'), '/');
        $measurementsUrl = $apiBase.'/api/iot/measurements';
        $heartbeatUrl = $apiBase.'/api/iot/heartbeat';
        $normalizedCode = str_replace('-', '', Str::upper($sensor->codigo));
        $sent = 0;

        try {
            for ($index = 1; $index <= $count; $index++) {
                if ($index > 1 && $interval > 0) {
                    sleep($interval);
                }

                $value = $manualValue !== null
                    ? (float) $manualValue
                    : $this->sampleValue((string) $sensor->tipoSensor->slug);
                $measurementId = 'SIM-'.$normalizedCode.'-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(10));

                $response = Http::acceptJson()
                    ->withToken($plainTextToken)
                    ->timeout(10)
                    ->post($measurementsUrl, [
                        'sensor_code' => $sensor->codigo,
                        'measurement_id' => $measurementId,
                        'value' => $value,
                        'unit' => $sensor->unidad_medida,
                        'measured_at' => now()->toISOString(),
                        'quality' => 'good',
                    ]);

                if (! $response->successful()) {
                    $message = $response->json('message') ?: 'la API devolvió HTTP '.$response->status();
                    $this->error('No se registró la medición: '.$message);

                    return self::FAILURE;
                }

                $sent++;
                $this->line(sprintf('%s · %s %s · %s', $measurementId, number_format($value, 1), $sensor->unidad_medida, $response->json('message', 'OK')));
            }

            $heartbeatResponse = Http::acceptJson()
                ->withToken($plainTextToken)
                ->timeout(10)
                ->post($heartbeatUrl, [
                    'sensor_code' => $sensor->codigo,
                    'device_id' => 'SIM-'.$normalizedCode,
                    'firmware_version' => 'simulador-1.0',
                    'signal_strength' => random_int(-75, -45),
                    'battery_level' => random_int(70, 100),
                    'status' => 'online',
                ]);

            if (! $heartbeatResponse->successful()) {
                $message = $heartbeatResponse->json('message') ?: 'la API devolvió HTTP '.$heartbeatResponse->status();
                $this->error('Las mediciones se enviaron, pero el heartbeat falló: '.$message);

                return self::FAILURE;
            }

            $this->info("Se enviaron {$sent} medición(es) y un heartbeat por la API IoT.");

            return self::SUCCESS;
        } catch (ConnectionException $exception) {
            $this->error('No se pudo conectar con la API. Inicia `php artisan serve` o configura SIPRED_IOT_API_URL.');

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('El simulador no pudo completar la solicitud. Revisa el log de Laravel.');

            report($exception);

            return self::FAILURE;
        } finally {
            $token->refresh();
            $token->forceFill([
                'estado' => SensorToken::STATUS_REVOKED,
                'revocado_at' => now(),
            ])->save();
        }
    }

    private function sampleValue(string $sensorType): float
    {
        [$minimum, $maximum, $precision] = match ($sensorType) {
            'temperatura' => [25, 40, 1],
            'humedad' => [40, 95, 1],
            'nivel-agua' => [0, 100, 1],
            'humo' => [0, 1, 0],
            default => [0, 100, 1],
        };

        if ($precision === 0) {
            return (float) random_int($minimum, $maximum);
        }

        $factor = 10 ** $precision;

        return random_int($minimum * $factor, $maximum * $factor) / $factor;
    }
}