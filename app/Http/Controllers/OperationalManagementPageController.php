<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use App\Models\TipoSensor;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationalManagementPageController extends Controller
{
    public function createZone(Request $request): View
    {
        $this->authorizePermission($request, 'zonas.crear');

        return view('operational.manage', [
            'entity' => 'zona', 'record' => new Zona(['nivel_riesgo' => 'bajo', 'estado' => 'activa']),
            'mode' => 'create', 'apiUrl' => '/api/gestion/zonas', 'returnUrl' => route('riesgos.index'),
        ]);
    }

    public function editZone(Request $request, Zona $zona): View
    {
        $this->authorizePermission($request, 'zonas.editar');

        return view('operational.manage', [
            'entity' => 'zona', 'record' => $zona, 'mode' => 'edit',
            'apiUrl' => '/api/gestion/zonas/'.$zona->getKey(), 'returnUrl' => route('riesgos.index'),
        ]);
    }

    public function createSensor(Request $request): View
    {
        $this->authorizePermission($request, 'sensores.crear');

        return view('operational.manage', [
            'entity' => 'sensor', 'record' => new Sensor(['estado' => Sensor::STATUS_DISCONNECTED, 'intervalo_lectura' => 60]),
            'mode' => 'create', 'apiUrl' => '/api/gestion/sensores', 'returnUrl' => route('sensores.index'),
            'types' => TipoSensor::query()->orderBy('nombre')->get(['id', 'nombre', 'unidad_medida']),
            'zones' => Zona::query()->orderBy('nombre')->get(['id', 'nombre', 'estado']),
        ]);
    }

    public function editSensor(Request $request, Sensor $sensor): View
    {
        $this->authorizePermission($request, 'sensores.editar');

        return view('operational.manage', [
            'entity' => 'sensor', 'record' => $sensor, 'mode' => 'edit',
            'apiUrl' => '/api/gestion/sensores/'.$sensor->getKey(), 'returnUrl' => route('sensores.index'),
            'types' => TipoSensor::query()->orderBy('nombre')->get(['id', 'nombre', 'unidad_medida']),
            'zones' => Zona::query()->orderBy('nombre')->get(['id', 'nombre', 'estado']),
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403, 'No tienes permiso para esta operación.');
    }
}
