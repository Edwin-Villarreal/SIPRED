<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\SensorUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreHeartbeatRequest;
use App\Models\Sensor;
use App\Services\HeartbeatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class HeartbeatController extends Controller
{
    public function store(StoreHeartbeatRequest $request, HeartbeatService $service): JsonResponse
    {
        /** @var Sensor $sensor */
        $sensor = $request->attributes->get('iot_sensor');

        try {
            $result = $service->receive($sensor, $request->validated(), $request);
            $heartbeat = $result['heartbeat'];

            Log::info('Heartbeat IoT recibido.', [
                'sensor_code' => $sensor->codigo,
                'status' => $heartbeat->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Heartbeat registrado correctamente.',
                'data' => [
                    'sensor_code' => $sensor->codigo,
                    'status' => $heartbeat->status,
                    'received_at' => $heartbeat->received_at?->toISOString(),
                    'event_created' => $result['event_created'],
                ],
            ], 201);
        } catch (SensorUnavailableException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'El sensor no está habilitado para reportar conexión.',
            ], 409);
        } catch (Throwable $exception) {
            Log::error('Error interno al recibir heartbeat IoT.', [
                'sensor_code' => $sensor->codigo,
                'exception' => $exception::class,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible procesar el heartbeat.',
            ], 500);
        }
    }
}