@extends('layouts.app')

@section('title', 'Dashboard')
@section('section', 'Dashboard')

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">CENTRO DE MONITOREO · EL ESPINAL</span>
            <h1>Dashboard</h1>
            <p>Sistema Inteligente de Prevención y Alerta Temprana de Desastres · Panel de situación.</p>
        </div>
        @if ($hayDatosDemo)
            <div class="phase-badge"><span class="phase-indicator" aria-hidden="true"></span> Fase 4 · Datos DEMO</div>
        @else
            <div class="phase-badge"><span class="phase-indicator" aria-hidden="true"></span> Fase 4 · Base de datos</div>
        @endif
    </section>

    <section class="kpi-grid" aria-label="Indicadores del sistema">
        <article class="kpi-card kpi-cyan">
            <div class="kpi-top"><span>SENSORES ACTIVOS</span><span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 12h3l2-6 4 12 3-9 2 3h4"/></svg></span></div>
            <div class="kpi-value">{{ number_format($sensoresActivosReales) }}</div>
            <div class="kpi-caption"><span class="kpi-status-dot"></span> {{ number_format($sensoresDemo) }} sensores DEMO · sin hardware real</div>
        </article>
        <article class="kpi-card kpi-green">
            <div class="kpi-top"><span>ALERTAS ACTIVAS</span><span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/></svg></span></div>
            <div class="kpi-value">{{ number_format($alertasAbiertasReales) }}</div>
            <div class="kpi-caption">{{ number_format($alertasDemoAbiertas) }} alertas abiertas DEMO · excluidas del total real</div>
        </article>
        <article class="kpi-card kpi-green">
            <div class="kpi-top"><span>RIESGOS CRÍTICOS</span><span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 10 18H2L12 3Z"/><path d="M12 9v5M12 17h.01"/></svg></span></div>
            <div class="kpi-value">{{ number_format($zonasCriticasReales) }}</div>
            <div class="kpi-caption">{{ number_format($zonasDemoCriticas) }} zonas críticas DEMO · sin coordenadas reales</div>
        </article>
        <article class="kpi-card kpi-blue">
            <div class="kpi-top"><span>MEDICIONES</span><span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 19V5M4 19h16M7 15l4-5 3 3 5-7"/></svg></span></div>
            <div class="kpi-value">{{ number_format($medicionesReales) }}</div>
            <div class="kpi-caption">{{ number_format($medicionesDemo) }} mediciones DEMO · no son telemetría real</div>
        </article>
    </section>

    <section class="dashboard-grid">
        <article class="panel chart-panel">
            <div class="panel-heading">
                <div><span class="panel-kicker">MONITOREO AMBIENTAL</span><h2>Temperatura · últimas 24 horas</h2></div>
                <span class="panel-period">Últimas 24 horas <span aria-hidden="true">⌄</span></span>
            </div>
            @if (count($temperatureChartPoints) > 0)
                @php
                    $temperatureCoordinates = collect($temperatureChartPoints)->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ');
                    $temperatureArea = '28,150 '.collect($temperatureChartPoints)->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ').' 692,150';
                @endphp
                <div class="measurement-chart-source">Serie {{ $temperatureChartOrigin }} · temperatura</div>
                <div class="measurement-chart">
                    <svg viewBox="0 0 720 165" role="img" aria-label="Temperatura de las últimas 24 horas; serie {{ $temperatureChartOrigin }}">
                        <line class="measurement-chart-grid" x1="28" y1="33" x2="692" y2="33" />
                        <line class="measurement-chart-grid" x1="28" y1="89" x2="692" y2="89" />
                        <line class="measurement-chart-grid" x1="28" y1="150" x2="692" y2="150" />
                        @if (count($temperatureChartPoints) > 1)
                            <polygon class="measurement-chart-area" points="{{ $temperatureArea }}" />
                        @endif
                        <polyline class="measurement-chart-line" points="{{ $temperatureCoordinates }}" />
                        @foreach ($temperatureChartPoints as $point)
                            <circle class="measurement-chart-point" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4"><title>{{ number_format($point['value'], 1) }} °C · {{ $point['label'] }}</title></circle>
                        @endforeach
                    </svg>
                    <div class="measurement-chart-axis"><span>Hace 24 h</span><span>Ahora</span></div>
                </div>
            @else
                <div class="chart-empty" role="status">
                    <div class="chart-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 19V5M4 19h16M7 15l4-5 3 3 5-7"/></svg></div>
                    <strong>Sin temperatura en las últimas 24 horas</strong>
                    <span>Al recibir lecturas de temperatura, la serie aparecerá aquí.</span>
                </div>
            @endif
            @if ($medicionesRecientes->isEmpty())
                <div class="measurement-empty">Aún no hay mediciones registradas.</div>
            @else
                <div class="measurement-feed" aria-label="Últimas mediciones registradas">
                    @foreach ($medicionesRecientes as $medicion)
                        <div class="measurement-feed-row">
                            <div class="measurement-feed-sensor">
                                <strong>{{ $medicion->sensor?->nombre ?? 'Sensor retirado' }}</strong>
                                <span>{{ $medicion->sensor?->codigo ?? 'Sin código' }} · {{ $medicion->medida_at?->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="measurement-feed-value">
                                <strong>{{ number_format((float) $medicion->valor, 2) }} {{ $medicion->unidad }}</strong>
                                @if ($medicion->origen === \App\Models\Medicion::ORIGIN_SIMULATOR)
                                    <span class="record-demo-tag">DEMO</span>
                                @else
                                    <span>{{ $medicion->calidad_dato }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif            <div class="chart-legend"><span>La serie muestra una sola fuente a la vez: prioriza datos reales y, si no existen, muestra DEMO por separado.</span><a href="{{ route('mediciones.index') }}">Ver historial →</a></div>
        </article>

        <article class="panel map-panel">
            <div class="panel-heading">
                <div><span class="panel-kicker">VISTA TERRITORIAL</span><h2>Mapa de riesgos</h2></div>
                <span class="map-status">{{ $zonasActivas }} zonas · {{ $zonasConUbicacion }} ubicaciones</span>
            </div>
            <div class="map-placeholder" role="img" aria-label="Vista de mapa pendiente; las zonas de demostración no tienen coordenadas reales">
                <div class="map-lines" aria-hidden="true"></div>
                <div class="map-crosshair" aria-hidden="true"><span></span></div>
                <div class="map-placeholder-label"><span class="map-pin-small" aria-hidden="true">⌖</span><strong>Ubicaciones pendientes</strong><span>{{ $zonasActivas }} zonas registradas · {{ $zonasConUbicacion }} con coordenadas</span></div>
                <span class="map-note">Las zonas DEMO no tienen coordenadas reales y no representan sensores instalados.</span>
            </div>
            <div class="map-footer"><span class="map-legend-dot"></span> Mapa sin ubicaciones físicas configuradas</div>
        </article>
    </section>

    <section class="dashboard-grid lower-grid">
        <article class="panel list-panel">
            <div class="panel-heading">
                <div><span class="panel-kicker">SEGUIMIENTO</span><h2>Alertas recientes</h2></div>
                <span class="count-badge">{{ $alertasRecientes->count() }}</span>
            </div>
            @forelse ($alertasRecientes as $alerta)
                <div class="empty-list">
                    <span class="empty-list-mark" aria-hidden="true">!</span>
                    <div>
                        <strong>{{ $alerta->titulo }}</strong>
                        <span>{{ $alerta->sensor?->codigo ?? 'Sistema' }} · {{ ucfirst($alerta->nivel) }} · {{ ucfirst($alerta->estado) }} · {{ $alerta->fecha_hora_inicio?->format('d/m H:i') }}</span>
                        @if ($alerta->origen === \App\Models\Alerta::ORIGIN_SIMULATOR)
                            <span class="record-demo-tag">DEMO · escenario de prueba, no es una emergencia real</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-list"><span class="empty-list-mark" aria-hidden="true">✓</span><div><strong>Todo despejado</strong><span>No hay alertas reales registradas.</span></div></div>
            @endforelse
        </article>
        <article class="panel list-panel">
            <div class="panel-heading">
                <div><span class="panel-kicker">ACTIVIDAD DEL SISTEMA</span><h2>Eventos recientes</h2></div>
                <span class="count-badge">{{ $eventosRecientes->count() }}</span>
            </div>
            @forelse ($eventosRecientes as $evento)
                <div class="empty-list">
                    <span class="empty-list-mark event-mark" aria-hidden="true">↗</span>
                    <div>
                        <strong>{{ $evento->titulo }}</strong>
                        <span>{{ $evento->sensor?->codigo ?? 'Sistema' }} · {{ $evento->ocurrio_at?->format('d/m H:i') }}</span>
                        @if ($evento->origen === 'simulador')
                            <span class="record-demo-tag">DEMO · evento de prueba</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-list"><span class="empty-list-mark event-mark" aria-hidden="true">↗</span><div><strong>Sin eventos registrados</strong><span>Conexiones, mediciones y alertas se registrarán aquí.</span></div></div>
            @endforelse
        </article>
    </section>

    <section class="foundation-note">
        <div class="foundation-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7l-8-4Z"/><path d="m9 12 2 2 4-4"/></svg></div>
        <div>
            @if ($hayDatosDemo)
                <strong>Datos de demostración</strong>
                <span>Los registros DEMO son sintéticos, no provienen de hardware ni representan condiciones reales del territorio. No se han cargado coordenadas reales.</span>
            @else
                <strong>Base de datos lista</strong>
                <span>No hay datos DEMO ni telemetría real. Las zonas, sensores y lecturas aparecerán al registrarlos.</span>
            @endif
        </div>
        <span class="foundation-tag">SIPRED · FASE 4</span>
    </section>
@endsection
