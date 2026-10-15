@extends('layouts.app')

@section('title', 'Detalle de medición')
@section('section', 'Mediciones')

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">DETALLE DE LECTURA</span>
            <h1>Medición</h1>
            <p>{{ $medicion->measurement_id }}</p>
        </div>
        <div class="measurement-page-actions">
            <a class="phase-badge" href="{{ route('mediciones.index') }}">← Volver al historial</a>
        </div>
    </section>

    <section class="panel measurement-page-panel">
        <div class="panel-heading">
            <div><span class="panel-kicker">{{ $medicion->sensor?->codigo ?? 'SENSOR' }}</span><h2>{{ $medicion->sensor?->nombre ?? 'Sensor retirado' }}</h2></div>
            <span class="measurement-origin {{ $medicion->origen === 'simulador' ? 'measurement-origin-demo' : '' }}">{{ $medicion->origen === 'simulador' ? 'DEMO · SIMULADOR' : strtoupper($medicion->origen) }}</span>
        </div>
        <div class="measurement-detail-grid">
            <div class="measurement-detail-item"><span>Tipo de sensor</span><strong>{{ $medicion->sensor?->tipoSensor?->nombre ?? '—' }}</strong></div>
            <div class="measurement-detail-item"><span>Zona</span><strong>{{ $medicion->sensor?->zona?->nombre ?? '—' }}@if($medicion->sensor?->zona?->is_demo) · DEMO @endif</strong></div>
            <div class="measurement-detail-item"><span>Valor</span><strong>{{ number_format((float) $medicion->valor, 2) }} {{ $medicion->unidad }}</strong></div>
            <div class="measurement-detail-item"><span>Fecha de medición</span><strong>{{ $medicion->medida_at?->format('d/m/Y H:i:s') ?? '—' }}</strong></div>
            <div class="measurement-detail-item"><span>Fecha de recepción</span><strong>{{ $medicion->received_at?->format('d/m/Y H:i:s') ?? '—' }}</strong></div>
            <div class="measurement-detail-item"><span>Origen</span><strong>{{ $medicion->origen === 'simulador' ? 'DEMO · simulador' : strtoupper($medicion->origen) }}</strong></div>
            <div class="measurement-detail-item"><span>Calidad del dato</span><strong>{{ $medicion->calidad_dato }}</strong></div>
            <div class="measurement-detail-item"><span>Código del sensor</span><strong>{{ $medicion->sensor?->codigo ?? '—' }}</strong></div>
            <div class="measurement-detail-item"><span>Identificador único</span><strong>{{ $medicion->measurement_id }}</strong></div>
        </div>
        @if ($medicion->origen === 'simulador')
            <div class="foundation-note"><div><strong>Registro de demostración</strong><span>Esta lectura es sintética; no proviene de un ESP32 ni de un sensor instalado.</span></div></div>
        @endif
    </section>
@endsection