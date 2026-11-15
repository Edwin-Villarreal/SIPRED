<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ZoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'zonas.ver');

        $zones = Zona::query()->orderBy('nombre')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $zones->items(),
            'meta' => ['current_page' => $zones->currentPage(), 'last_page' => $zones->lastPage(), 'total' => $zones->total()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'zonas.crear');

        $zone = Zona::query()->create($this->validatedAttributes($request));

        return response()->json(['success' => true, 'data' => $zone], 201);
    }

    public function show(Request $request, Zona $zona): JsonResponse
    {
        $this->authorizePermission($request, 'zonas.ver');

        return response()->json(['success' => true, 'data' => $zona->loadCount('sensores')]);
    }

    public function update(Request $request, Zona $zona): JsonResponse
    {
        $this->authorizePermission($request, 'zonas.editar');
        $zona->fill($this->validatedAttributes($request, $zona, partial: true))->save();

        return response()->json(['success' => true, 'data' => $zona->fresh()]);
    }

    public function destroy(Request $request, Zona $zona): JsonResponse
    {
        $this->authorizePermission($request, 'zonas.eliminar');

        if ($zona->sensores()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede retirar la zona mientras tenga sensores asociados.',
            ], 409);
        }

        // La eliminación es lógica; conserva la zona en la base de datos.
        $zona->delete();

        return response()->json(['success' => true, 'message' => 'Zona retirada.']);
    }

    private function validatedAttributes(Request $request, ?Zona $zona = null, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'nombre' => [...$required, 'string', 'max:120'],
            'slug' => [...$required, 'string', 'max:140', Rule::unique('zonas', 'slug')->ignore($zona?->getKey())],
            'descripcion' => ['sometimes', 'nullable', 'string'],
            'municipio' => ['sometimes', 'nullable', 'string', 'max:100'],
            'departamento' => ['sometimes', 'nullable', 'string', 'max:100'],
            'pais' => ['sometimes', 'nullable', 'string', 'max:100'],
            'latitud' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'nivel_riesgo' => ['sometimes', 'required', Rule::in(['bajo', 'medio', 'alto', 'critico'])],
            'estado' => ['sometimes', 'required', Rule::in(['activa', 'inactiva'])],
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
