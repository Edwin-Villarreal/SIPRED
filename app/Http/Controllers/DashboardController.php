<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Evento;
use App\Models\Medicion;
use App\Models\Sensor;
use App\Models\Zona;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $sensoresDemo = Sensor::deDemostracion()->count();
        $alertasDemoAbiertas = Alerta::deDemostracion()->abiertas()->count();
        $zonasDemoCriticas = Zona::deDemostracion()
            ->where('estado', 'activa')
            ->where('nivel_riesgo', 'critico')
            ->count();
        $medicionesDemo = Medicion::deDemostracion()->count();

        $temperatureBaseQuery = Medicion::query()
            ->where('medida_at', '>=', now()->subDay())
            ->whereHas('sensor.tipoSensor', fn ($query) => $query->where('slug', 'temperatura'));
        $hasRealTemperature = (clone $temperatureBaseQuery)->reales()->exists();
        $temperatureQuery = (clone $temperatureBaseQuery)
            ->when(
                $hasRealTemperature,
                fn ($query) => $query->reales(),
                fn ($query) => $query->deDemostracion(),
            );
        $temperatureReadings = $temperatureQuery
            ->latest('medida_at')
            ->limit(24)
            ->get(['id', 'sensor_id', 'valor', 'unidad', 'origen', 'medida_at'])
            ->sortBy('medida_at')
            ->values();
        $temperatureChartPoints = $this->chartPoints($temperatureReadings);

        return view('dashboard', [
            'sensoresActivosReales' => Sensor::reales()->activos()->count(),
            'sensoresDemo' => $sensoresDemo,
            'alertasAbiertasReales' => Alerta::reales()->abiertas()->count(),
            'alertasDemoAbiertas' => $alertasDemoAbiertas,
            'zonasCriticasReales' => Zona::query()
                ->where('is_demo', false)
                ->where('estado', 'activa')
                ->where('nivel_riesgo', 'critico')
                ->count(),
            'zonasDemoCriticas' => $zonasDemoCriticas,
            'medicionesReales' => Medicion::reales()->count(),
            'medicionesDemo' => $medicionesDemo,
            'zonasActivas' => Zona::query()->where('estado', 'activa')->count(),
            'zonasConUbicacion' => Zona::query()
                ->where('estado', 'activa')
                ->whereNotNull('latitud')
                ->whereNotNull('longitud')
                ->count(),
            'medicionesRecientes' => Medicion::query()
                ->with('sensor')
                ->latest('medida_at')
                ->limit(5)
                ->get(),
            'alertasRecientes' => Alerta::query()
                ->with('sensor')
                ->latest('fecha_hora_inicio')
                ->limit(4)
                ->get(),
            'eventosRecientes' => Evento::query()
                ->with('sensor')
                ->latest('ocurrio_at')
                ->limit(4)
                ->get(),
            'temperatureChartPoints' => $temperatureChartPoints,
            'temperatureChartOrigin' => $hasRealTemperature ? 'REAL' : 'DEMO',
            'hayDatosDemo' => $sensoresDemo > 0 || $alertasDemoAbiertas > 0 || $zonasDemoCriticas > 0 || $medicionesDemo > 0,
        ]);
    }

    /** @param Collection<int, Medicion> $readings
     *  @return array<int, array{x: float, y: float, value: float, label: string}>
     */
    private function chartPoints(Collection $readings): array
    {
        if ($readings->isEmpty()) {
            return [];
        }

        $values = $readings->map(fn (Medicion $measurement): float => (float) $measurement->valor);
        $minimum = $values->min();
        $maximum = $values->max();
        $range = max($maximum - $minimum, 1.0);
        $count = $readings->count();

        return $readings->values()->map(function (Medicion $measurement, int $index) use ($count, $minimum, $range): array {
            $value = (float) $measurement->valor;

            return [
                'x' => $count === 1 ? 360.0 : 28.0 + (664.0 * $index / ($count - 1)),
                'y' => 145.0 - (($value - $minimum) / $range * 112.0),
                'value' => $value,
                'label' => $measurement->medida_at?->format('d/m H:i') ?? '',
            ];
        })->all();
    }
}