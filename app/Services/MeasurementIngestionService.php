<?php

namespace App\Services;

use App\Exceptions\MeasurementIdConflictException;
use App\Exceptions\SensorUnavailableException;
use App\Models\Evento;
use App\Models\Medicion;
use App\Models\Sensor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MeasurementIngestionService
{
    /**
     * Record one validated measurement and its event atomically.
     *
     * @param  array<string, mixed>  $payload
     * @return array{measurement: Medicion, created: bool, status: 'created'|'duplicate'}
     */
    public function ingest(array $payload, ?Sensor $authenticatedSensor = null): array
    {
        $data = Validator::make($payload, [
            'sensor_code' => ['required', 'string', 'max:64'],
            'measurement_id' => ['required', 'string', 'max:191'],
            'value' => ['required', 'numeric'],
            'unit' => ['required', 'string', 'max:32'],
            'measured_at' => ['required', 'date'],
            'quality' => ['nullable', 'in:good,warning,invalid,unknown'],
            'origin' => ['nullable', 'in:simulador,esp32,usuario,sistema'],
            'metadata' => ['nullable', 'array'],
        ])->validate();

        $sensor = $authenticatedSensor ?? Sensor::query()
            ->where('codigo', $data['sensor_code'])
            ->firstOrFail();

        if ($sensor->codigo !== $data['sensor_code']) {
            throw new MeasurementIdConflictException('El token no corresponde al sensor indicado.');
        }

        if ($sensor->estado !== Sensor::STATUS_ACTIVE) {
            throw new SensorUnavailableException('El sensor no está habilitado para enviar mediciones.');
        }

        $measurementId = trim($data['measurement_id']);
        $receivedAt = now();
        $measuredAt = \Illuminate\Support\Carbon::parse($data['measured_at']);
        $origin = $data['origin'] ?? ($sensor->is_demo ? Medicion::ORIGIN_SIMULATOR : 'esp32');

        try {
            return DB::transaction(function () use ($sensor, $data, $measurementId, $receivedAt, $measuredAt, $origin): array {
                $lockedSensor = Sensor::query()->lockForUpdate()->findOrFail($sensor->getKey());
                $existing = Medicion::query()
                    ->where('measurement_id', $measurementId)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    return $this->duplicateResult($existing, $lockedSensor, $receivedAt);
                }

                $measurement = $lockedSensor->mediciones()->create([
                    'measurement_id' => $measurementId,
                    'valor' => $data['value'],
                    'unidad' => $data['unit'],
                    'calidad_dato' => $data['quality'] ?? 'unknown',
                    'origen' => $origin,
                    'medida_at' => $measuredAt,
                    'received_at' => $receivedAt,
                    'metadata' => $data['metadata'] ?? null,
                ]);

                $lockedSensor->forceFill([
                    'ultima_medicion_at' => $lockedSensor->mediciones()->max('medida_at'),
                    'ultima_conexion_at' => $receivedAt,
                ])->save();

                Evento::query()->create([
                    'usuario_id' => null,
                    'sensor_id' => $lockedSensor->getKey(),
                    'zona_id' => $lockedSensor->zona_id,
                    'alerta_id' => null,
                    'tipo' => 'medicion',
                    'titulo' => 'Medición recibida',
                    'descripcion' => 'El sensor envió una medición por la API IoT.',
                    'nivel' => 'informativa',
                    'origen' => $origin,
                    'ocurrio_at' => $receivedAt,
                    'metadata' => [
                        'measurement_id' => $measurementId,
                        'calidad_dato' => $measurement->calidad_dato,
                    ],
                ]);

                return [
                    'measurement' => $measurement,
                    'created' => true,
                    'status' => 'created',
                ];
            });
        } catch (QueryException $exception) {
            $existing = Medicion::query()->where('measurement_id', $measurementId)->first();

            if ($existing === null) {
                throw $exception;
            }

            $currentSensor = Sensor::query()->findOrFail($sensor->getKey());

            return DB::transaction(fn (): array => $this->duplicateResult($existing, $currentSensor, now()));
        }
    }

    /** @return array{measurement: Medicion, created: false, status: 'duplicate'} */
    private function duplicateResult(Medicion $existing, Sensor $sensor, \Illuminate\Support\Carbon $receivedAt): array
    {
        if ((int) $existing->sensor_id !== (int) $sensor->getKey()) {
            throw new MeasurementIdConflictException('El measurement_id ya existe asociado a otro sensor.');
        }

        $sensor->forceFill(['ultima_conexion_at' => $receivedAt])->save();

        return [
            'measurement' => $existing,
            'created' => false,
            'status' => 'duplicate',
        ];
    }
}