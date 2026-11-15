@extends('layouts.app')

@section('title', 'Configuración')
@section('section', 'Configuración')

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">ADMINISTRACIÓN · CONFIGURACIÓN</span>
            <h1>Configuración</h1>
            <p>Administra los métodos permitidos para recuperar el acceso a las cuentas.</p>
        </div>
    </section>

    @include('settings.partials.recovery-credentials', [
        'settings' => $settings,
        'returnTo' => 'configuracion.index',
    ])

    @include('settings.partials.recovery-options', [
        'settings' => $settings,
        'canManage' => true,
        'standalone' => true,
    ])
@endsection