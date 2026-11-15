<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'eventos.ver');
        $events = Evento::query()->with(['sensor:id,codigo,nombre', 'zona:id,nombre', 'user:id,name'])
            ->latest('ocurrio_at')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'meta' => ['current_page' => $events->currentPage(), 'last_page' => $events->lastPage(), 'total' => $events->total()],
        ]);
    }

    public function show(Request $request, Evento $evento): JsonResponse
    {
        $this->authorizePermission($request, 'eventos.ver');

        return response()->json(['success' => true, 'data' => $evento->load(['sensor', 'zona', 'alerta', 'user'])]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        if ($request->user() === null && app()->environment('local') && config('sipred.local_crud_without_login')) {
            return;
        }

        abort_unless($request->user()?->hasPermission($permission), 403, 'No tienes permiso para esta operación.');
    }
}
