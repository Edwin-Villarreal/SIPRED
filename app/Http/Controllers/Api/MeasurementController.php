<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\MeasurementIdConflictException;
use App\Exceptions\SensorUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMeasurementRequest;
use App\Models\Medicion;
use App\Models\Sensor;
use App\Services\MeasurementIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class MeasurementController extends Controller
{
    public function store(StoreMeasurementRequest $request, MeasurementIngestionService $service): JsonResponse
    {
        /** @var Sensor $sensor */
        $sensor = $request->attributes->get('iot_sensor');
        $payload = $request->validated();
        $payload['origin'] = $sensor->is_demo ? Medicion::ORIGIN_SIMULATOR : 'esp32';

        try {
            $result = $service->ingest($payload, $sensor);
            $measurement = $result['measurement'];
            $data = [
                'measurement_id' => $measurement->measurement_id,
                'sensor_code' => $sensor->codigo,
                'value' => (float) $measurement->valor,
                'unit' => $measurement->unidad,
                'measured_at' => $measurement->medida_at?->toISOString(),
                'received_at' => $measurement->received_at?->toISOString(),
                'origin' => $measurement->origen,
            ];

            if (! $result['created']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Medición ya registrada.',
                    'data' => $data,
                ], 200);
            }

            Log::info('Medición IoT recibida.', [
                'sensor_code' => $sensor->codigo,
                'measurement_id' => $measurement->measurement_id,
                'origin' => $measurement->origen,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Medición registrada correctamente.',
                'data' => $data,
            ], 201);
        } catch (MeasurementIdConflictException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'El measurement_id ya está asociado a otro sensor.',
            ], 409);
        } catch (SensorUnavailableException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'El sensor no está habilitado para enviar mediciones.',
            ], 409);
        } catch (Throwable $exception) {
            Log::error('Error interno al recibir una medición IoT.', [
                'sensor_code' => $sensor->codigo,
                'exception' => $exception::class,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible procesar la medición.',
            ], 500);
        }
    }
}