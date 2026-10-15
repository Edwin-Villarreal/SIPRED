<?php

namespace App\Services;

use App\Exceptions\SensorUnavailableException;
use App\Models\Evento;
use App\Models\Sensor;
use App\Models\SensorHeartbeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HeartbeatService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{heartbeat: SensorHeartbeat, event_created: bool}
     */
    public function receive(Sensor $sensor, array $data, Request $request): array
    {
        if ($sensor->estado !== Sensor::STATUS_ACTIVE) {
            throw new SensorUnavailableException('El sensor no está habilitado para reportar conexión.');
        }

        return DB::transaction(function () use ($sensor, $data, $request): array {
            $lockedSensor = Sensor::query()->lockForUpdate()->findOrFail($sensor->getKey());
            $previous = $lockedSensor->heartbeats()->latest('received_at')->first();
            $receivedAt = now();
            $origin = $lockedSensor->is_demo ? 'simulador' : 'esp32';
            $heartbeat = $lockedSensor->heartbeats()->create([
                'device_id' => $data['device_id'] ?? null,
                'ip_address' => $request->ip(),
                'signal_strength' => $data['signal_strength'] ?? null,
                'battery_level' => $data['battery_level'] ?? null,
                'firmware_version' => $data['firmware_version'] ?? null,
                'status' => $data['status'],
                'received_at' => $receivedAt,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $lockedSensor->forceFill(['ultima_conexion_at' => $receivedAt])->save();
            $eventCreated = $previous === null || $previous->status !== $heartbeat->status;

            if ($eventCreated) {
                $eventType = match ($heartbeat->status) {
                    'online' => 'conexion',
                    'offline' => 'desconexion',
                    default => 'sistema',
                };
                $title = match ($heartbeat->status) {
                    'online' => 'Dispositivo conectado',
                    'offline' => 'Dispositivo reportó desconexión',
                    default => 'Estado de dispositivo actualizado',
                };

                Evento::query()->create([
                    'usuario_id' => null,
                    'sensor_id' => $lockedSensor->getKey(),
                    'zona_id' => $lockedSensor->zona_id,
                    'alerta_id' => null,
                    'tipo' => $eventType,
                    'titulo' => $title,
                    'descripcion' => 'Estado reportado por el heartbeat autenticado.',
                    'nivel' => $heartbeat->status === 'offline' ? 'advertencia' : 'informativa',
                    'origen' => $origin,
                    'ocurrio_at' => $receivedAt,
                    'metadata' => ['heartbeat_status' => $heartbeat->status],
                ]);
            }

            return ['heartbeat' => $heartbeat, 'event_created' => $eventCreated];
        });
    }
}