@extends('layouts.app')

@php
    $isZone = $entity === 'zona';
    $title = ($mode === 'create' ? 'Registrar ' : 'Editar ').($isZone ? 'zona' : 'sensor');
@endphp

@section('title', ucfirst($title))
@section('section', $isZone ? 'Mapa de riesgos' : 'Sensores')

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ $isZone ? 'GESTIÓN DE ZONAS' : 'INVENTARIO DE SENSORES' }} · SIPRED</span>
            <h1>{{ ucfirst($title) }}</h1>
            <p>{{ $isZone ? 'Administra los campos definidos para las zonas de riesgo.' : 'Administra el inventario y la ubicación de los sensores registrados.' }}</p>
        </div>
        <a class="measurement-page-actions-link" href="{{ $returnUrl }}">Volver al listado</a>
    </section>

    <section class="panel measurement-page-panel operational-form-panel">
        <div class="panel-heading">
            <div><span class="panel-kicker">{{ $mode === 'create' ? 'NUEVO REGISTRO' : 'REGISTRO EXISTENTE' }}</span><h2>{{ ucfirst($title) }}</h2></div>
        </div>
        <p class="crud-feedback" data-crud-feedback role="status" aria-live="polite" hidden></p>

        <form class="operational-form-grid" data-crud-form data-api-url="{{ $apiUrl }}" data-api-method="{{ $mode === 'create' ? 'POST' : 'PUT' }}" data-return-url="{{ $returnUrl }}">
            @if ($isZone)
                <div class="measurement-filter">
                    <label for="zone-name">Nombre</label>
                    <input id="zone-name" name="nombre" maxlength="120" value="{{ old('nombre', $record->nombre) }}" required>
                    <small class="field-error" data-field-error="nombre"></small>
                </div>
                <div class="measurement-filter">
                    <label for="zone-slug">Identificador (slug)</label>
                    <input id="zone-slug" name="slug" maxlength="140" value="{{ old('slug', $record->slug) }}" required>
                    <small class="field-error" data-field-error="slug"></small>
                </div>
                <div class="measurement-filter">
                    <label for="zone-risk">Nivel de riesgo</label>
                    <select id="zone-risk" name="nivel_riesgo" required>
                        @foreach (['bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto', 'critico' => 'Crítico'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('nivel_riesgo', $record->nivel_riesgo) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="field-error" data-field-error="nivel_riesgo"></small>
                </div>
                <div class="measurement-filter">
                    <label for="zone-state">Estado</label>
                    <select id="zone-state" name="estado" required>
                        @foreach (['activa' => 'Activa', 'inactiva' => 'Inactiva'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('estado', $record->estado) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="field-error" data-field-error="estado"></small>
                </div>
                <div class="measurement-filter">
                    <label for="zone-municipio">Municipio</label>
                    <input id="zone-municipio" name="municipio" maxlength="100" value="{{ old('municipio', $record->municipio) }}">
                    <small class="field-error" data-field-error="municipio"></small>
                </div>
                <div class="measurement-filter">
                    <label for="zone-departamento">Departamento</label>
                    <input id="zone-departamento" name="departamento" maxlength="100" value="{{ old('departamento', $record->departamento) }}">
                    <small class="field-error" data-field-error="departamento"></small>
                </div>
                <div class="measurement-filter">
                    <label for="zone-pais">País</label>
                    <input id="zone-pais" name="pais" maxlength="100" value="{{ old('pais', $record->pais) }}">
                    <small class="field-error" data-field-error="pais"></small>
                </div>
                <div class="measurement-filter">
                    <label for="zone-latitude">Latitud</label>
                    <input id="zone-latitude" type="number" name="latitud" step="any" min="-90" max="90" value="{{ old('latitud', $record->latitud) }}">
                    <small class="field-error" data-field-error="latitud"></small>
                </div>
                <div class="measurement-filter">
                    <label for="zone-longitude">Longitud</label>
                    <input id="zone-longitude" type="number" name="longitud" step="any" min="-180" max="180" value="{{ old('longitud', $record->longitud) }}">
                    <small class="field-error" data-field-error="longitud"></small>
                </div>
                <div class="measurement-filter operational-form-wide">
                    <label for="zone-description">Descripción</label>
                    <textarea id="zone-description" name="descripcion" rows="3">{{ old('descripcion', $record->descripcion) }}</textarea>
                    <small class="field-error" data-field-error="descripcion"></small>
                </div>
                <label class="operational-checkbox"><input type="checkbox" name="is_demo" value="1" @checked(old('is_demo', $record->is_demo))> Registro de demostración (DEMO)</label>
            @else
                <div class="measurement-filter">
                    <label for="sensor-code">Código</label>
                    <input id="sensor-code" name="codigo" maxlength="64" value="{{ old('codigo', $record->codigo) }}" required>
                    <small class="field-error" data-field-error="codigo"></small>
                </div>
                <div class="measurement-filter">
                    <label for="sensor-name">Nombre</label>
                    <input id="sensor-name" name="nombre" maxlength="120" value="{{ old('nombre', $record->nombre) }}" required>
                    <small class="field-error" data-field-error="nombre"></small>
                </div>
                <div class="measurement-filter">
                    <label for="sensor-type">Tipo de sensor</label>
                    <select id="sensor-type" name="tipo_sensor_id" required>
                        <option value="">Selecciona un tipo</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected(old('tipo_sensor_id', $record->tipo_sensor_id) == $type->id)>{{ $type->nombre }}</option>
                        @endforeach
                    </select>
                    <small class="field-error" data-field-error="tipo_sensor_id"></small>
                </div>
                <div class="measurement-filter">
                    <label for="sensor-zone">Zona</label>
                    <select id="sensor-zone" name="zona_id" required>
                        <option value="">Selecciona una zona</option>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}" @selected(old('zona_id', $record->zona_id) == $zone->id)>{{ $zone->nombre }}{{ $zone->estado === 'inactiva' ? ' · Inactiva' : '' }}</option>
                        @endforeach
                    </select>
                    <small class="field-error" data-field-error="zona_id"></small>
                </div>
                <div class="measurement-filter">
                    <label for="sensor-unit">Unidad de medida</label>
                    <input id="sensor-unit" name="unidad_medida" maxlength="32" value="{{ old('unidad_medida', $record->unidad_medida) }}" required>
                    <small class="field-error" data-field-error="unidad_medida"></small>
                </div>
                <div class="measurement-filter">
                    <label for="sensor-state">Estado</label>
                    <select id="sensor-state" name="estado" required>
                        @foreach (['activo' => 'Activo', 'inactivo' => 'Inactivo', 'mantenimiento' => 'Mantenimiento', 'sin_conexion' => 'Sin conexión'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('estado', $record->estado) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="field-error" data-field-error="estado"></small>
                </div>
                <div class="measurement-filter">
                    <label for="sensor-model">Modelo</label>
                    <input id="sensor-model" name="modelo" maxlength="100" value="{{ old('modelo', $record->modelo) }}">
                    <small class="field-error" data-field-error="modelo"></small>
                </div>
                <div class="measurement-filter">
                    <label for="sensor-maker">Fabricante</label>
                    <input id="sensor-maker" name="fabricante" maxlength="100" value="{{ old('fabricante', $record->fabricante) }}">
                    <small class="field-error" data-field-error="fabricante"></small>
                </div>
                <div class="measurement-filter">
                    <label for="sensor-interval">Intervalo de lectura (segundos)</label>
                    <input id="sensor-interval" type="number" name="intervalo_lectura" min="1" step="1" value="{{ old('intervalo_lectura', $record->intervalo_lectura ?? 60) }}" required>
                    <small class="field-error" data-field-error="intervalo_lectura"></small>
                </div>
                <div class="measurement-filter">
                    <label for="sensor-threshold-min">Umbral mínimo</label>
                    <input id="sensor-threshold-min" type="number" name="umbral_minimo" step="any" value="{{ old('umbral_minimo', $record->umbral_minimo) }}">
                    <small class="field-error" data-field-error="umbral_minimo"></small>
                </div>
                <div class="measurement-filter">
                    <label for="sensor-threshold-max">Umbral máximo</label>
                    <input id="sensor-threshold-max" type="number" name="umbral_maximo" step="any" value="{{ old('umbral_maximo', $record->umbral_maximo) }}">
                    <small class="field-error" data-field-error="umbral_maximo"></small>
                </div>
                <div class="measurement-filter">
                    <label for="sensor-installed">Fecha de instalación</label>
                    <input id="sensor-installed" type="date" name="fecha_instalacion" value="{{ old('fecha_instalacion', $record->fecha_instalacion?->format('Y-m-d')) }}">
                    <small class="field-error" data-field-error="fecha_instalacion"></small>
                </div>
                <div class="measurement-filter operational-form-wide">
                    <label for="sensor-description">Descripción</label>
                    <textarea id="sensor-description" name="descripcion" rows="3">{{ old('descripcion', $record->descripcion) }}</textarea>
                    <small class="field-error" data-field-error="descripcion"></small>
                </div>
                <label class="operational-checkbox"><input type="checkbox" name="is_demo" value="1" @checked(old('is_demo', $record->is_demo))> Registro de demostración (DEMO)</label>
            @endif

            <div class="measurement-filter-actions operational-form-wide">
                <button class="btn-sipred" type="submit" data-submit-label="{{ $mode === 'create' ? 'Registrar' : 'Guardar cambios' }}">{{ $mode === 'create' ? 'Registrar' : 'Guardar cambios' }}</button>
                <a class="measurement-page-actions-link" href="{{ $returnUrl }}">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
