<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\Sensor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SensorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'sensores.ver');
        $sensors = Sensor::query()->with(['tipoSensor:id,nombre', 'zona:id,nombre'])->orderBy('codigo')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $sensors->items(),
            'meta' => ['current_page' => $sensors->currentPage(), 'last_page' => $sensors->lastPage(), 'total' => $sensors->total()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'sensores.crear');
        $sensor = Sensor::query()->create($this->validatedAttributes($request));

        return response()->json(['success' => true, 'data' => $sensor->load(['tipoSensor:id,nombre', 'zona:id,nombre'])], 201);
    }

    public function show(Request $request, Sensor $sensor): JsonResponse
    {
        $this->authorizePermission($request, 'sensores.ver');

        return response()->json(['success' => true, 'data' => $sensor->load(['tipoSensor', 'zona'])]);
    }

    public function update(Request $request, Sensor $sensor): JsonResponse
    {
        $this->authorizePermission($request, 'sensores.editar');
        $sensor->fill($this->validatedAttributes($request, $sensor, partial: true))->save();

        return response()->json(['success' => true, 'data' => $sensor->fresh(['tipoSensor', 'zona'])]);
    }

    public function destroy(Request $request, Sensor $sensor): JsonResponse
    {
        $this->authorizePermission($request, 'sensores.eliminar');

        // El borrado lógico mantiene mediciones, eventos y alertas históricos.
        $sensor->delete();

        return response()->json(['success' => true, 'message' => 'Sensor retirado.']);
    }

    private function validatedAttributes(Request $request, ?Sensor $sensor = null, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'tipo_sensor_id' => [...$required, 'integer', 'exists:tipos_sensores,id'],
            'zona_id' => [...$required, 'integer', 'exists:zonas,id'],
            'codigo' => [...$required, 'string', 'max:64', Rule::unique('sensores', 'codigo')->ignore($sensor?->getKey())],
            'nombre' => [...$required, 'string', 'max:120'],
            'descripcion' => ['sometimes', 'nullable', 'string'],
            'unidad_medida' => [...$required, 'string', 'max:32'],
            'modelo' => ['sometimes', 'nullable', 'string', 'max:100'],
            'fabricante' => ['sometimes', 'nullable', 'string', 'max:100'],
            'estado' => ['sometimes', 'required', Rule::in([
                Sensor::STATUS_ACTIVE,
                Sensor::STATUS_INACTIVE,
                Sensor::STATUS_MAINTENANCE,
                Sensor::STATUS_DISCONNECTED,
            ])],
            'intervalo_lectura' => ['sometimes', 'required', 'integer', 'min:1', 'max:4294967295'],
            'umbral_minimo' => ['sometimes', 'nullable', 'decimal:0,4', 'between:-999999999999.9999,999999999999.9999'],
            'umbral_maximo' => ['sometimes', 'nullable', 'decimal:0,4', 'between:-999999999999.9999,999999999999.9999'],
            'fecha_instalacion' => ['sometimes', 'nullable', 'date'],
            'is_demo' => ['sometimes', 'boolean'],
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        if ($request->user() === null && app()->environment('local') && config('sipred.local_crud_without_login')) {
            return;
        }

        abort_unless($request->user()?->hasPermission($permission), 403, 'No tienes permiso para esta operación.');
    }
}
