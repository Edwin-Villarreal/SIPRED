<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Evento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'alertas.ver');
        $alerts = Alerta::query()->with(['sensor:id,codigo,nombre', 'zona:id,nombre'])
            ->latest('fecha_hora_inicio')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $alerts->items(),
            'meta' => ['current_page' => $alerts->currentPage(), 'last_page' => $alerts->lastPage(), 'total' => $alerts->total()],
        ]);
    }

    public function show(Request $request, Alerta $alerta): JsonResponse
    {
        $this->authorizePermission($request, 'alertas.ver');

        return response()->json(['success' => true, 'data' => $alerta->load(['sensor', 'zona'])]);
    }

    public function update(Request $request, Alerta $alerta): JsonResponse
    {
        $this->authorizePermission($request, 'alertas.gestionar');
        $data = $request->validate([
            'estado' => ['required', 'string', Rule::in([Alerta::STATUS_ACKNOWLEDGED, Alerta::STATUS_RESOLVED])],
        ]);
        $newState = $data['estado'];

        if (! in_array($alerta->estado, [Alerta::STATUS_ACTIVE, Alerta::STATUS_ACKNOWLEDGED], true)) {
            return response()->json(['success' => false, 'message' => 'La alerta ya no está abierta.'], 409);
        }

        if ($alerta->estado === $newState) {
            return response()->json(['success' => true, 'data' => $alerta]);
        }

        $previousState = $alerta->estado;
        DB::transaction(function () use ($alerta, $newState, $previousState, $request): void {
            $now = now();
            $attributes = ['estado' => $newState];

            if ($newState === Alerta::STATUS_ACKNOWLEDGED) {
                $attributes['reconocida_por'] = $request->user()?->getKey();
                $attributes['reconocida_at'] = $now;
            } else {
                $attributes['resuelta_por'] = $request->user()?->getKey();
                $attributes['resuelta_at'] = $now;
                $attributes['fecha_hora_fin'] = $now;
            }

            $alerta->forceFill($attributes)->save();
            Evento::query()->create([
                'usuario_id' => $request->user()?->getKey(),
                'sensor_id' => $alerta->sensor_id,
                'zona_id' => $alerta->zona_id,
                'alerta_id' => $alerta->getKey(),
                'tipo' => 'alerta',
                'titulo' => $newState === Alerta::STATUS_ACKNOWLEDGED ? 'Alerta reconocida' : 'Alerta resuelta',
                'descripcion' => $alerta->titulo,
                'nivel' => $alerta->nivel,
                'origen' => 'usuario',
                'ocurrio_at' => $now,
                'metadata' => ['estado_anterior' => $previousState, 'estado_nuevo' => $newState],
            ]);
        });

        return response()->json(['success' => true, 'data' => $alerta->fresh(['sensor', 'zona'])]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        if ($request->user() === null && app()->environment('local') && config('sipred.local_crud_without_login')) {
            return;
        }

        abort_unless($request->user()?->hasPermission($permission), 403, 'No tienes permiso para esta operación.');
    }
}
