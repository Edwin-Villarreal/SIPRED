<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\TipoSensor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->user() === null && app()->environment('local') && config('sipred.local_crud_without_login')) {
            return response()->json([
                'success' => true,
                'data' => TipoSensor::query()->orderBy('nombre')->get(['id', 'nombre', 'unidad_medida', 'tipo_dato', 'estado']),
            ]);
        }

        abort_unless($request->user()?->hasPermission('sensores.ver'), 403, 'No tienes permiso para consultar tipos de sensor.');

        return response()->json([
            'success' => true,
            'data' => TipoSensor::query()->orderBy('nombre')->get(['id', 'nombre', 'unidad_medida', 'tipo_dato', 'estado']),
        ]);
    }
}
