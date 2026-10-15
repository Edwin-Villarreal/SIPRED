<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sensor;
use Illuminate\Http\JsonResponse;

class SensorStatusController extends Controller
{
    public function show(string $sensor): JsonResponse
    {
        /** @var Sensor $sensorModel */
        $sensorModel = request()->attributes->get('iot_sensor');
        $lastMeasurement = $sensorModel->mediciones()->latest('medida_at')->first();
        $lastHeartbeat = $sensorModel->heartbeats()->latest('received_at')->first();
        $lastSeenAt = $sensorModel->ultima_conexion_at;
        $timeoutSeconds = (int) config('sipred.iot_connectivity_timeout_seconds', 300);
        $isRecent = $lastSeenAt !== null && $lastSeenAt->greaterThanOrEqualTo(now()->subSeconds($timeoutSeconds));
        $offlineReportedAfterLastSeen = $lastHeartbeat !== null
            && $lastHeartbeat->status === 'offline'
            && ($lastSeenAt === null || $lastHeartbeat->received_at?->greaterThanOrEqualTo($lastSeenAt));
        $connectivity = $isRecent && ! $offlineReportedAfterLastSeen ? 'online' : 'offline';

        return response()->json([
            'success' => true,
            'data' => [
                'sensor_code' => $sensorModel->codigo,
                'status' => $sensorModel->estado,
                'connectivity' => $connectivity,
                'last_seen_at' => $lastSeenAt?->toISOString(),
                'last_measurement_at' => $sensorModel->ultima_medicion_at?->toISOString(),
                'last_measurement' => $lastMeasurement === null ? null : [
                    'value' => (float) $lastMeasurement->valor,
                    'unit' => $lastMeasurement->unidad,
                    'origin' => $lastMeasurement->origen,
                ],
                'last_heartbeat_at' => $lastHeartbeat?->received_at?->toISOString(),
            ],
        ]);
    }
}