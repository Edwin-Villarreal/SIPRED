@extends('layouts.app')

@section('title', 'Mediciones')
@section('section', 'Mediciones')

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">TELEMETRÍA REGISTRADA</span>
            <h1>Mediciones</h1>
            <p>Historial de lecturas recibidas; los datos del simulador están marcados como DEMO.</p>
        </div>
        <div class="measurement-page-actions">
            <span class="phase-badge"><span class="phase-indicator" aria-hidden="true"></span> {{ number_format($mediciones->total()) }} registros</span>
        </div>
    </section>

    <section class="panel measurement-page-panel">
        <div class="panel-heading">
            <div><span class="panel-kicker">BÚSQUEDA Y FILTROS</span><h2>Consultar historial</h2></div>
            <a class="measurement-page-actions-link" href="{{ route('dashboard') }}">Volver al dashboard</a>
        </div>

        <form class="measurement-filters" method="GET" action="{{ route('mediciones.index') }}">
            <div class="measurement-filter">
                <label for="filter-q">Búsqueda</label>
                <input id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Código, sensor o measurement ID">
            </div>
            <div class="measurement-filter">
                <label for="filter-sensor">Sensor</label>
                <select id="filter-sensor" name="sensor_id">
                    <option value="">Todos</option>
                    @foreach ($sensores as $sensor)
                        <option value="{{ $sensor->id }}" @selected(($filters['sensor_id'] ?? '') == $sensor->id)>{{ $sensor->codigo }} · {{ $sensor->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="measurement-filter">
                <label for="filter-type">Tipo</label>
                <select id="filter-type" name="tipo_sensor_id">
                    <option value="">Todos</option>
                    @foreach ($tiposSensores as $tipo)
                        <option value="{{ $tipo->id }}" @selected(($filters['tipo_sensor_id'] ?? '') == $tipo->id)>{{ $tipo->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="measurement-filter">
                <label for="filter-zone">Zona</label>
                <select id="filter-zone" name="zona_id">
                    <option value="">Todas</option>
                    @foreach ($zonas as $zona)
                        <option value="{{ $zona->id }}" @selected(($filters['zona_id'] ?? '') == $zona->id)>{{ $zona->nombre }}@if($zona->is_demo) · DEMO @endif</option>
                    @endforeach
                </select>
            </div>
            <div class="measurement-filter">
                <label for="filter-origin">Origen</label>
                <select id="filter-origin" name="origen">
                    <option value="">Todos</option>
                    @foreach (['simulador' => 'DEMO · simulador', 'esp32' => 'ESP32', 'usuario' => 'Usuario', 'sistema' => 'Sistema'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['origen'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="measurement-filter">
                <label for="filter-quality">Calidad</label>
                <select id="filter-quality" name="calidad">
                    <option value="">Todas</option>
                    @foreach (['good' => 'Correcta', 'warning' => 'Advertencia', 'invalid' => 'Inválida', 'unknown' => 'Desconocida'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['calidad'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="measurement-filter">
                <label for="filter-from">Fecha desde</label>
                <input id="filter-from" type="date" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}">
            </div>
            <div class="measurement-filter">
                <label for="filter-to">Fecha hasta</label>
                <input id="filter-to" type="date" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}">
            </div>
            <div class="measurement-filter">
                <label for="filter-order">Ordenar por</label>
                <select id="filter-order" name="orden">
                    @foreach (['medida_at' => 'Fecha de medición', 'received_at' => 'Fecha de recepción', 'valor' => 'Valor', 'measurement_id' => 'ID de medición'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['orden'] ?? 'medida_at') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="measurement-filter">
                <label for="filter-direction">Dirección</label>
                <select id="filter-direction" name="direccion">
                    <option value="desc" @selected(($filters['direccion'] ?? 'desc') === 'desc')>Descendente</option>
                    <option value="asc" @selected(($filters['direccion'] ?? '') === 'asc')>Ascendente</option>
                </select>
            </div>
            <div class="measurement-filter-actions">
                <button class="btn-sipred" type="submit">Aplicar filtros</button>
                <a class="measurement-page-actions-link" href="{{ route('mediciones.index') }}">Limpiar</a>
            </div>
        </form>

        @if ($mediciones->isEmpty())
            <div class="measurement-empty" role="status">No hay mediciones que coincidan con estos filtros.</div>
        @else
            <div class="measurement-table-wrap">
                <table class="measurement-table">
                    <thead><tr><th>MEDICIÓN</th><th>SENSOR</th><th>TIPO</th><th>ZONA</th><th>VALOR</th><th>MEDIDA</th><th>RECIBIDA</th><th>ORIGEN</th><th>CALIDAD</th></tr></thead>
                    <tbody>
                        @foreach ($mediciones as $medicion)
                            <tr>
                                <td><a href="{{ route('mediciones.show', $medicion) }}">{{ $medicion->measurement_id }}</a></td>
                                <td>{{ $medicion->sensor?->codigo ?? '—' }}<small>{{ $medicion->sensor?->nombre ?? 'Sensor retirado' }}</small></td>
                                <td>{{ $medicion->sensor?->tipoSensor?->nombre ?? '—' }}</td>
                                <td>{{ $medicion->sensor?->zona?->nombre ?? '—' }}@if($medicion->sensor?->zona?->is_demo)<small>DEMO</small>@endif</td>
                                <td>{{ number_format((float) $medicion->valor, 2) }} {{ $medicion->unidad }}</td>
                                <td>{{ $medicion->medida_at?->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $medicion->received_at?->format('d/m/Y H:i:s') }}</td>
                                <td><span class="measurement-origin {{ $medicion->origen === 'simulador' ? 'measurement-origin-demo' : '' }}">{{ $medicion->origen === 'simulador' ? 'DEMO' : $medicion->origen }}</span></td>
                                <td>{{ $medicion->calidad_dato }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <nav class="measurement-pagination" aria-label="Paginación de mediciones">
                <span>Mostrando {{ $mediciones->firstItem() }}–{{ $mediciones->lastItem() }} de {{ $mediciones->total() }}</span>
                <div>
                    @if ($mediciones->previousPageUrl()) <a href="{{ $mediciones->previousPageUrl() }}">← Anterior</a> @endif
                    <span> · Página {{ $mediciones->currentPage() }} de {{ $mediciones->lastPage() }} · </span>
                    @if ($mediciones->nextPageUrl()) <a href="{{ $mediciones->nextPageUrl() }}">Siguiente →</a> @endif
                </div>
            </nav>
        @endif
    </section>
@endsection