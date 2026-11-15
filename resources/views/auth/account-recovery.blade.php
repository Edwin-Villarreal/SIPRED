@extends('layouts.app')

@section('title', 'Seguridad de la cuenta')
@section('section', 'Seguridad de la cuenta')

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">SEGURIDAD DE LA CUENTA</span>
            <h1>Seguridad de la cuenta</h1>
            <p>Administra tus códigos y archivo de respaldo.</p>
        </div>
    </section>

    @include('settings.partials.recovery-credentials', [
        'settings' => $settings,
        'returnTo' => 'account.recovery',
    ])

    <div class="recovery-warning recovery-always-on"><strong>Cambio de contraseña siempre está activado.</strong> Antes, debes verificar tu cuenta con uno de los métodos habilitados.</div>
@endsection