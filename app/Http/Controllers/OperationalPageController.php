<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Evento;
use App\Models\Medicion;
use App\Models\Sensor;
use App\Models\User;
use App\Models\Zona;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OperationalPageController extends Controller
{
    public function riskMap(): View
    {
        $zones = Zona::query()
            ->where('estado', 'activa')
            ->withCount('sensores')
            ->withCount(['alertas as alertas_abiertas_count' => fn (Builder $query) => $query->whereIn('estado', [
                Alerta::STATUS_ACTIVE,
                Alerta::STATUS_ACKNOWLEDGED,
            ])])
            ->orderByRaw("CASE nivel_riesgo WHEN 'critico' THEN 1 WHEN 'alto' THEN 2 WHEN 'medio' THEN 3 WHEN 'bajo' THEN 4 ELSE 5 END")
            ->orderBy('nombre')
            ->get();

        return $this->table(
            section: 'Plataforma',
            title: 'Mapa de riesgos',
            description: 'Vista de zonas activas, nivel de riesgo, sensores registrados y alertas abiertas. Las coordenadas se muestran cuando están disponibles.',
            stats: [
                ['label' => 'Zonas activas', 'value' => Zona::query()->where('estado', 'activa')->count()],
                ['label' => 'Riesgo alto o crítico', 'value' => Zona::query()->where('estado', 'activa')->whereIn('nivel_riesgo', ['alto', 'critico'])->count()],
                ['label' => 'Sensores activos', 'value' => Sensor::query()->where('estado', Sensor::STATUS_ACTIVE)->count()],
                ['label' => 'Alertas abiertas', 'value' => Alerta::abiertas()->count()],
            ],
            columns: [
                ['key' => 'zona', 'label' => 'ZONA'],
                ['key' => 'ubicacion', 'label' => 'UBICACIÓN'],
                ['key' => 'riesgo', 'label' => 'NIVEL DE RIESGO'],
                ['key' => 'sensores', 'label' => 'SENSORES'],
                ['key' => 'alertas', 'label' => 'ALERTAS ABIERTAS'],
                ['key' => 'coordenadas', 'label' => 'COORDENADAS'],
            ],
            records: $zones,
            format: fn (Zona $zone): array => [
                'zona' => ['value' => $zone->nombre, 'hint' => $zone->is_demo ? 'DEMO' : null],
                'ubicacion' => collect([$zone->municipio, $zone->departamento])->filter()->implode(', ') ?: 'Sin ubicación registrada',
                'riesgo' => ucfirst($zone->nivel_riesgo),
                'sensores' => (string) $zone->sensores_count,
                'alertas' => (string) $zone->alertas_abiertas_count,
                'coordenadas' => $zone->latitud !== null && $zone->longitud !== null
                    ? $zone->latitud.', '.$zone->longitud
                    : 'Sin coordenadas',
            ],
        );
    }

    public function sensors(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $sensors = Sensor::query()
            ->with(['tipoSensor', 'zona'])
            ->withCount('mediciones')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('codigo', 'like', '%'.$search.'%')
                        ->orWhere('nombre', 'like', '%'.$search.'%')
                        ->orWhereHas('zona', fn (Builder $zoneQuery) => $zoneQuery->where('nombre', 'like', '%'.$search.'%'));
                });
            })
            ->orderBy('codigo')
            ->paginate(15)
            ->withQueryString();

        return $this->table(
            section: 'Plataforma',
            title: 'Sensores',
            description: 'Inventario de sensores, zona asignada, conectividad registrada y acceso al historial de lecturas.',
            stats: [
                ['label' => 'Total', 'value' => Sensor::query()->count()],
                ['label' => 'Activos', 'value' => Sensor::query()->where('estado', Sensor::STATUS_ACTIVE)->count()],
                ['label' => 'Con datos', 'value' => Sensor::query()->whereNotNull('ultima_medicion_at')->count()],
                ['label' => 'DEMO', 'value' => Sensor::query()->where('is_demo', true)->count()],
            ],
            columns: [
                ['key' => 'codigo', 'label' => 'CÓDIGO'],
                ['key' => 'sensor', 'label' => 'SENSOR'],
                ['key' => 'tipo', 'label' => 'TIPO'],
                ['key' => 'zona', 'label' => 'ZONA'],
                ['key' => 'estado', 'label' => 'ESTADO'],
                ['key' => 'mediciones', 'label' => 'LECTURAS'],
                ['key' => 'ultima', 'label' => 'ÚLTIMO DATO'],
            ],
            records: $sensors,
            format: fn (Sensor $sensor): array => [
                'codigo' => [
                    'value' => $sensor->codigo,
                    'hint' => $sensor->is_demo ? 'DEMO · Ver mediciones' : 'Ver mediciones',
                    'href' => route('mediciones.index', ['sensor_id' => $sensor->getKey()]),
                ],
                'sensor' => $sensor->nombre,
                'tipo' => $sensor->tipoSensor?->nombre ?? '—',
                'zona' => $sensor->zona?->nombre ?? '—',
                'estado' => ucfirst(str_replace('_', ' ', $sensor->estado)),
                'mediciones' => (string) $sensor->mediciones_count,
                'ultima' => $sensor->ultima_medicion_at?->format('d/m/Y H:i') ?? 'Sin lecturas',
            ],
            searchable: true,
        );
    }

    public function alerts(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $alerts = Alerta::query()
            ->with(['sensor', 'zona'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('titulo', 'like', '%'.$search.'%')
                        ->orWhere('mensaje', 'like', '%'.$search.'%')
                        ->orWhereHas('sensor', fn (Builder $sensorQuery) => $sensorQuery->where('codigo', 'like', '%'.$search.'%'))
                        ->orWhereHas('zona', fn (Builder $zoneQuery) => $zoneQuery->where('nombre', 'like', '%'.$search.'%'));
                });
            })
            ->latest('fecha_hora_inicio')
            ->paginate(15)
            ->withQueryString();

        return $this->table(
            section: 'Plataforma',
            title: 'Alertas',
            description: 'Seguimiento de alertas registradas por zona y sensor, con nivel, origen y estado actual.',
            stats: [
                ['label' => 'Abiertas', 'value' => Alerta::abiertas()->count()],
                ['label' => 'Críticas', 'value' => Alerta::abiertas()->where('nivel', Alerta::LEVEL_CRITICAL)->count()],
                ['label' => 'Reconocidas', 'value' => Alerta::query()->where('estado', Alerta::STATUS_ACKNOWLEDGED)->count()],
                ['label' => 'Resueltas', 'value' => Alerta::query()->where('estado', Alerta::STATUS_RESOLVED)->count()],
            ],
            columns: [
                ['key' => 'fecha', 'label' => 'INICIO'],
                ['key' => 'nivel', 'label' => 'NIVEL'],
                ['key' => 'alerta', 'label' => 'ALERTA'],
                ['key' => 'sensor', 'label' => 'SENSOR / ZONA'],
                ['key' => 'estado', 'label' => 'ESTADO'],
                ['key' => 'origen', 'label' => 'ORIGEN'],
            ],
            records: $alerts,
            format: fn (Alerta $alert): array => [
                'fecha' => $alert->fecha_hora_inicio?->format('d/m/Y H:i') ?? '—',
                'nivel' => ucfirst($alert->nivel),
                'alerta' => ['value' => $alert->titulo, 'hint' => $alert->mensaje],
                'sensor' => [
                    'value' => $alert->sensor?->codigo ?? '—',
                    'hint' => $alert->zona?->nombre ?? 'Zona sin registro',
                ],
                'estado' => ucfirst($alert->estado),
                'origen' => $alert->origen === Alerta::ORIGIN_SIMULATOR ? 'DEMO' : ucfirst($alert->origen),
            ],
            searchable: true,
        );
    }

    public function events(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $events = Evento::query()
            ->with(['sensor', 'zona', 'user'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('titulo', 'like', '%'.$search.'%')
                        ->orWhere('descripcion', 'like', '%'.$search.'%')
                        ->orWhere('tipo', 'like', '%'.$search.'%')
                        ->orWhereHas('sensor', fn (Builder $sensorQuery) => $sensorQuery->where('codigo', 'like', '%'.$search.'%'))
                        ->orWhereHas('zona', fn (Builder $zoneQuery) => $zoneQuery->where('nombre', 'like', '%'.$search.'%'));
                });
            })
            ->latest('ocurrio_at')
            ->paginate(20)
            ->withQueryString();

        return $this->table(
            section: 'Plataforma',
            title: 'Eventos',
            description: 'Cronología de actividad de sensores, mediciones, conexiones y alertas.',
            stats: [
                ['label' => 'Eventos totales', 'value' => Evento::query()->count()],
                ['label' => 'Últimas 24 horas', 'value' => Evento::query()->where('ocurrio_at', '>=', now()->subDay())->count()],
                ['label' => 'Alertas', 'value' => Evento::query()->where('tipo', 'alerta')->count()],
                ['label' => 'Conexiones', 'value' => Evento::query()->where('tipo', 'conexion')->count()],
            ],
            columns: [
                ['key' => 'fecha', 'label' => 'FECHA'],
                ['key' => 'tipo', 'label' => 'TIPO'],
                ['key' => 'evento', 'label' => 'EVENTO'],
                ['key' => 'sensor', 'label' => 'SENSOR / ZONA'],
                ['key' => 'nivel', 'label' => 'NIVEL'],
                ['key' => 'origen', 'label' => 'ORIGEN'],
            ],
            records: $events,
            format: fn (Evento $event): array => [
                'fecha' => $event->ocurrio_at?->format('d/m/Y H:i:s') ?? '—',
                'tipo' => ucfirst(str_replace('_', ' ', $event->tipo)),
                'evento' => ['value' => $event->titulo, 'hint' => $event->descripcion],
                'sensor' => [
                    'value' => $event->sensor?->codigo ?? '—',
                    'hint' => $event->zona?->nombre ?? 'Zona sin registro',
                ],
                'nivel' => ucfirst($event->nivel),
                'origen' => $event->origen === 'simulador' ? 'DEMO' : ucfirst($event->origen),
            ],
            searchable: true,
        );
    }

    public function reports(): View
    {
        $today = CarbonImmutable::today();
        $from = $today->subDays(6);
        $days = collect();

        for ($day = $from; $day->lessThanOrEqualTo($today); $day = $day->addDay()) {
            $count = Medicion::query()
                ->whereBetween('medida_at', [$day->startOfDay(), $day->endOfDay()])
                ->count();
            $days->push((object) [
                'fecha' => $day->format('d/m/Y'),
                'lecturas' => $count,
            ]);
        }

        return $this->table(
            section: 'Plataforma',
            title: 'Reportes',
            description: 'Resumen operativo generado con las mediciones, sensores, zonas y alertas existentes.',
            stats: [
                ['label' => 'Lecturas registradas', 'value' => Medicion::query()->count()],
                ['label' => 'Lecturas en 7 días', 'value' => Medicion::query()->where('medida_at', '>=', $from->startOfDay())->count()],
                ['label' => 'Sensores activos', 'value' => Sensor::query()->where('estado', Sensor::STATUS_ACTIVE)->count()],
                ['label' => 'Alertas abiertas', 'value' => Alerta::abiertas()->count()],
            ],
            columns: [
                ['key' => 'fecha', 'label' => 'DÍA'],
                ['key' => 'lecturas', 'label' => 'MEDICIONES RECIBIDAS'],
            ],
            records: $days,
            format: fn (object $day): array => [
                'fecha' => $day->fecha,
                'lecturas' => (string) $day->lecturas,
            ],
            emptyMessage: 'Todavía no hay mediciones registradas para este periodo.',
        );
    }

    public function users(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $users = User::query()
            ->with('roles')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return $this->table(
            section: 'Administración',
            title: 'Usuarios',
            description: 'Consulta de cuentas y roles disponibles en SIPRED. Las contraseñas nunca se muestran.',
            stats: [
                ['label' => 'Cuentas', 'value' => User::query()->count()],
                ['label' => 'Activas', 'value' => User::query()->where('status', User::STATUS_ACTIVE)->count()],
                ['label' => 'Inactivas', 'value' => User::query()->where('status', User::STATUS_INACTIVE)->count()],
                ['label' => 'Roles', 'value' => \App\Models\Role::query()->count()],
            ],
            columns: [
                ['key' => 'usuario', 'label' => 'USUARIO'],
                ['key' => 'correo', 'label' => 'CORREO'],
                ['key' => 'roles', 'label' => 'ROLES'],
                ['key' => 'estado', 'label' => 'ESTADO'],
                ['key' => 'acceso', 'label' => 'ÚLTIMO ACCESO'],
            ],
            records: $users,
            format: fn (User $user): array => [
                'usuario' => $user->name,
                'correo' => $user->email,
                'roles' => $user->roles->pluck('name')->implode(', ') ?: 'Sin rol asignado',
                'estado' => ucfirst($user->status ?? 'activo'),
                'acceso' => $user->last_access_at?->format('d/m/Y H:i') ?? 'Sin registro',
            ],
            searchable: true,
        );
    }

    public function configuration(): View
    {
        $records = collect([
            (object) ['name' => 'Aplicación', 'value' => config('app.name')],
            (object) ['name' => 'Entorno', 'value' => app()->environment()],
            (object) ['name' => 'URL local', 'value' => config('app.url')],
            (object) ['name' => 'Conexión de base de datos', 'value' => config('database.default').' · '.config('database.connections.'.config('database.default').'.driver')],
            (object) ['name' => 'URL de API IoT', 'value' => config('sipred.iot_api_url')],
            (object) ['name' => 'Tiempo para considerar sensor desconectado', 'value' => config('sipred.iot_connectivity_timeout_seconds').' segundos'],
        ]);

        return $this->table(
            section: 'Administración',
            title: 'Configuración',
            description: 'Parámetros activos del entorno. Las credenciales y secretos no se muestran en esta pantalla.',
            stats: [
                ['label' => 'Entorno', 'value' => app()->environment()],
                ['label' => 'Base de datos', 'value' => strtoupper((string) config('database.default'))],
                ['label' => 'API IoT', 'value' => 'Configurada'],
                ['label' => 'Timeout IoT', 'value' => config('sipred.iot_connectivity_timeout_seconds').' s'],
            ],
            columns: [
                ['key' => 'name', 'label' => 'PARÁMETRO'],
                ['key' => 'value', 'label' => 'VALOR ACTIVO'],
            ],
            records: $records,
            format: fn (object $record): array => [
                'name' => $record->name,
                'value' => (string) ($record->value ?? '—'),
            ],
            emptyMessage: 'No hay parámetros para mostrar.',
        );
    }

    private function table(
        string $section,
        string $title,
        string $description,
        array $stats,
        array $columns,
        Collection|LengthAwarePaginator $records,
        callable $format,
        bool $searchable = false,
        string $emptyMessage = 'No hay registros disponibles.',
    ): View {
        $pagination = $records instanceof LengthAwarePaginator ? $records : null;
        $items = $pagination?->getCollection() ?? collect($records);
        $rows = $items->map($format);

        return view('operational.index', [
            'section' => $section,
            'title' => $title,
            'description' => $description,
            'stats' => $stats,
            'columns' => $columns,
            'rows' => $rows,
            'pagination' => $pagination,
            'searchable' => $searchable,
            'search' => request()->query('q', ''),
            'emptyMessage' => $emptyMessage,
        ]);
    }
}