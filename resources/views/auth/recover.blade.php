<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#051428">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="description" content="Recuperación privada del acceso a una cuenta SIPRED.">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/branding/sipred.jpeg') }}">
    <title>Recuperar acceso · SIPRED</title>
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
                <div class="login-brand-copy">
                    <span class="eyebrow">SEGURIDAD DE LA CUENTA</span>
                    <p class="login-brand-description">Sistema Inteligente de Prevención y Alerta Temprana de Desastres</p>
                </div>
            </div>
            <div class="login-aside-content">
                <span class="eyebrow">RECUPERAR ACCESO</span>
                <h2>Recupera el acceso<br><em>a tu cuenta SIPRED.</em></h2>
                <p>Verifica tu cuenta para continuar.</p>
            </div>
            <div class="login-aside-foot">
                <span class="login-location">El Espinal, Tolima, Colombia</span>
                <span>Protección de cuenta</span>
            </div>
        </aside>

        <section class="login-panel recovery-panel" aria-labelledby="recovery-title">
            <div class="login-heading">
                <span class="eyebrow">RECUPERAR ACCESO</span>
                <h1 id="recovery-title">¿Problemas para entrar?</h1>
                <p>Verifica tu cuenta con uno de los métodos disponibles para cambiar tu contraseña.</p>
            </div>

            @if ($errors->has('recovery'))
                <div class="field-error recovery-message" role="alert">{{ $errors->first('recovery') }}</div>
            @endif
            @if ($errors->has('email'))
                <div class="field-error recovery-message" role="alert">{{ $errors->first('email') }}</div>
            @endif
            @if ($errors->has('recovery_file'))
                <div class="field-error recovery-message" role="alert">{{ $errors->first('recovery_file') }}</div>
            @endif

            @if ($settings->recovery_codes_enabled)
                <form method="POST" action="{{ route('password.recovery.code') }}" class="login-form recovery-form">
                    @csrf
                    <h2>Códigos de respaldo</h2>
                    <div class="field-group">
                        <label for="code-email">Correo electrónico</label>
                        <input id="code-email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
                    </div>
                    <div class="field-group">
                        <label for="recovery-code">Código de respaldo</label>
                        <input id="recovery-code" name="code" type="text" autocomplete="one-time-code" required maxlength="24" placeholder="HF25-35jw">
                    </div>
                    @error('code')<span class="field-error" role="alert">{{ $message }}</span>@enderror
                    <button class="btn btn-sipred login-submit" type="submit">Continuar <span aria-hidden="true">→</span></button>
                </form>
            @endif

            @if ($settings->recovery_file_enabled)
                <form method="POST" action="{{ route('password.recovery.file') }}" class="login-form recovery-form" enctype="multipart/form-data">
                    @csrf
                    <h2>Archivo de respaldo</h2>
                    <div class="field-group">
                        <label for="file-email">Correo electrónico</label>
                        <input id="file-email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
                    </div>
                    <div class="field-group">
                        <label for="recovery-file">Archivo de respaldo</label>
                        <p class="recovery-form-help">Selecciona el archivo de respaldo que guardaste anteriormente.</p>
                        <input id="recovery-file" name="recovery_file" type="file" accept=".json,application/json" required>
                    </div>
                    <button class="btn btn-sipred login-submit" type="submit">Validar archivo <span aria-hidden="true">→</span></button>
                </form>
            @endif

            @if (! $settings->recovery_codes_enabled && ! $settings->recovery_file_enabled)
                <div class="recovery-message" role="status">Por ahora no hay métodos de respaldo disponibles. Ponte en contacto con el administrador de SIPRED.</div>
            @endif

            <a class="login-recovery-link recovery-back-link" href="{{ route('login') }}">← Volver al inicio de sesión</a>
            <div class="login-security"><span class="security-dot" aria-hidden="true"></span> Para cambiar tu contraseña, primero verifica tu cuenta</div>
        </section>
    </main>
</body>
</html>
