<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#051428">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="description" content="Restablecimiento privado de contraseña SIPRED tras verificar la cuenta.">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/branding/sipred.jpeg') }}">
    <title>Cambiar contraseña · SIPRED</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="sipred-body login-body">
    <main class="login-shell recovery-shell">
        <aside class="login-aside" aria-label="Identidad SIPRED">
            <div class="login-grid-pattern" aria-hidden="true"></div>
            <div class="login-brand-lockup">
                <span class="login-logo-frame">
                    <img class="login-logo" src="{{ asset('assets/branding/sipred.jpeg') }}" alt="Logo oficial SIPRED" width="1254" height="1254" decoding="async">
                </span>
                <div class="login-brand-copy"><span class="eyebrow">SEGURIDAD DE CUENTA</span><p class="login-brand-description">Sistema Inteligente de Prevención y Alerta Temprana de Desastres</p></div>
            </div>
            <div class="login-aside-content"><span class="eyebrow">CUENTA VERIFICADA</span><h2>Elige una contraseña<br><em>nueva para tu cuenta.</em></h2><p>Esta verificación solo puede usarse una vez.</p></div>
            <div class="login-aside-foot"><span class="login-location">El Espinal, Tolima, Colombia</span><span>Protección de cuenta</span></div>
        </aside>
        <section class="login-panel recovery-panel" aria-labelledby="reset-title">
            <div class="login-heading">
                <span class="eyebrow">SEGURIDAD DE LA CUENTA</span>
                <h1 id="reset-title">Cambiar contraseña</h1>
                <p>Usa al menos 12 caracteres para tu nueva contraseña.</p>
            </div>
            @if (session('status'))
                <div class="field-success" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="field-error recovery-message" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('password.recovery.update') }}" class="login-form recovery-form">
                @csrf
                <div class="field-group">
                    <label for="password">Nueva contraseña</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required minlength="12" autofocus>
                </div>
                <div class="field-group">
                    <label for="password_confirmation">Confirmar nueva contraseña</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="12">
                </div>
                <button class="btn btn-sipred login-submit" type="submit">Cambiar contraseña <span aria-hidden="true">→</span></button>
            </form>
            <div class="login-security"><span class="security-dot" aria-hidden="true"></span> Esta verificación solo puede usarse una vez</div>
        </section>
    </main>
</body>
</html>
