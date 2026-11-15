<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#051428">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="description" content="Acceso privado a la plataforma SIPRED de monitoreo y prevención.">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/branding/sipred.jpeg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/branding/sipred.jpeg') }}">
    <title>Iniciar sesión · SIPRED</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="sipred-body login-body">
    <main class="login-shell">
        <aside class="login-aside" aria-label="Identidad e información de SIPRED">
            <div class="login-grid-pattern" aria-hidden="true"></div>
            <div class="login-orbit" aria-hidden="true"><span></span><i></i><b></b></div>

            <div class="login-brand-lockup">
                <span class="login-logo-frame">
                    <img class="login-logo" src="{{ asset('assets/branding/sipred.jpeg') }}" alt="Logo SIPRED - Sistema Inteligente de Prevención y Alerta Temprana de Desastres" width="1254" height="1254" decoding="async">
                </span>
                <div class="login-brand-copy">
                    <span class="eyebrow">PLATAFORMA DE MONITOREO</span>
                    <p class="login-brand-description">Sistema Inteligente de Prevención y Alerta Temprana de Desastres</p>
                </div>
            </div>

            <div class="login-aside-content">
                <span class="eyebrow">PREVENCIÓN · MONITOREO · RESPUESTA</span>
                <h2>Anticipar el riesgo.<br><em>Proteger a la comunidad.</em></h2>
                <p>Monitoreo ambiental y alerta temprana para El Espinal, Tolima.</p>
            </div>

            <div class="login-aside-foot">
                <span class="login-location"><span class="location-pin" aria-hidden="true">⌖</span> El Espinal, Tolima, Colombia</span>
                <span>Plataforma en fase inicial</span>
            </div>
        </aside>

        <section class="login-panel" aria-labelledby="login-title">
            <div class="login-heading">
                <span class="eyebrow">CENTRO DE MONITOREO</span>
                <h1 id="login-title">Bienvenido</h1>
                <p>Accede a la plataforma SIPRED para consultar el estado del sistema.</p>
                @if (session('status'))<div class="field-success" role="status">{{ session('status') }}</div>@endif
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="login-form">
                @csrf
                <div class="field-group">
                    <label for="email">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="nombre@correo.com" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email')<span class="field-error" id="email-error">{{ $message }}</span>@enderror
                </div>
                <div class="field-group">
                    <label for="password">Contraseña</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Ingresa tu contraseña" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                    @error('password')<span class="field-error" id="password-error">{{ $message }}</span>@enderror
                </div>
                <label class="remember-control"><input type="checkbox" name="remember" value="1"><span>Recordar mi sesión</span></label>
                <a class="login-recovery-link" href="{{ route('password.recovery') }}">¿Problemas para entrar?</a>
                <button class="btn btn-sipred login-submit" type="submit">Ingresar <span aria-hidden="true">→</span></button>
            </form>
            <div class="login-security"><span class="security-dot" aria-hidden="true"></span> Acceso protegido con sesión segura</div>
        </section>
    </main>
</body>
</html>
