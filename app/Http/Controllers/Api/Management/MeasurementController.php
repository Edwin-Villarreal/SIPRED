<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\Medicion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeasurementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'mediciones.ver');
        $measurements = Medicion::query()->with(['sensor:id,codigo,nombre,tipo_sensor_id,zona_id', 'sensor.tipoSensor:id,nombre', 'sensor.zona:id,nombre'])
            ->latest('medida_at')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $measurements->items(),
            'meta' => ['current_page' => $measurements->currentPage(), 'last_page' => $measurements->lastPage(), 'total' => $measurements->total()],
        ]);
    }

    public function show(Request $request, Medicion $medicion): JsonResponse
    {
        $this->authorizePermission($request, 'mediciones.ver');

        return response()->json(['success' => true, 'data' => $medicion->load(['sensor.tipoSensor', 'sensor.zona'])]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        if ($request->user() === null && app()->environment('local') && config('sipred.local_crud_without_login')) {
            return;
        }

        abort_unless($request->user()?->hasPermission($permission), 403, 'No tienes permiso para esta operación.');
    }
}
