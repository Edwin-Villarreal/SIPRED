<?php

namespace App\Http\Controllers;

use App\Models\Medicion;
use App\Models\Sensor;
use App\Models\TipoSensor;
use App\Models\Zona;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeasurementPageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:191'],
            'sensor_id' => ['nullable', 'integer', 'exists:sensores,id'],
            'tipo_sensor_id' => ['nullable', 'integer', 'exists:tipos_sensores,id'],
            'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
            'origen' => ['nullable', 'in:simulador,esp32,usuario,sistema'],
            'calidad' => ['nullable', 'in:good,warning,invalid,unknown'],
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_desde'],
            'orden' => ['nullable', 'in:medida_at,received_at,valor,measurement_id'],
            'direccion' => ['nullable', 'in:asc,desc'],
        ]);

        $query = Medicion::query()->with(['sensor.tipoSensor', 'sensor.zona']);

        if (filled($filters['sensor_id'] ?? null)) {
            $query->where('sensor_id', $filters['sensor_id']);
        }

        if (filled($filters['tipo_sensor_id'] ?? null)) {
            $query->whereHas('sensor', fn (Builder $sensorQuery) => $sensorQuery->where('tipo_sensor_id', $filters['tipo_sensor_id']));
        }

        if (filled($filters['zona_id'] ?? null)) {
            $query->whereHas('sensor', fn (Builder $sensorQuery) => $sensorQuery->where('zona_id', $filters['zona_id']));
        }

        if (filled($filters['origen'] ?? null)) {
            $query->where('origen', $filters['origen']);
        }

        if (filled($filters['calidad'] ?? null)) {
            $query->where('calidad_dato', $filters['calidad']);
        }

        if (filled($filters['fecha_desde'] ?? null)) {
            $query->whereDate('medida_at', '>=', $filters['fecha_desde']);
        }

        if (filled($filters['fecha_hasta'] ?? null)) {
            $query->whereDate('medida_at', '<=', $filters['fecha_hasta']);
        }

        if (filled($filters['q'] ?? null)) {
            $term = '%'.trim($filters['q']).'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('measurement_id', 'like', $term)
                    ->orWhereHas('sensor', fn (Builder $sensorQuery) => $sensorQuery
                        ->where('codigo', 'like', $term)
                        ->orWhere('nombre', 'like', $term));
            });
        }

        $sort = $filters['orden'] ?? 'medida_at';
        $direction = $filters['direccion'] ?? 'desc';
        $mediciones = $query->orderBy($sort, $direction)->paginate(20)->withQueryString();

        return view('mediciones.index', [
            'mediciones' => $mediciones,
            'sensores' => Sensor::query()->orderBy('codigo')->get(['id', 'codigo', 'nombre']),
            'tiposSensores' => TipoSensor::query()->orderBy('nombre')->get(['id', 'nombre']),
            'zonas' => Zona::query()->orderBy('nombre')->get(['id', 'nombre']),
            'filters' => $filters,
        ]);
    }

    public function show(Medicion $medicion): View
    {
        $medicion->load(['sensor.tipoSensor', 'sensor.zona']);

        return view('mediciones.show', ['medicion' => $medicion]);
    }
}