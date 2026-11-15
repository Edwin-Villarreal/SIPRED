<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#051428">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="description" content="Plataforma privada SIPRED para consulta operativa de zonas, sensores, mediciones y alertas.">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/branding/sipred.jpeg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/branding/sipred.jpeg') }}">
    <title>@yield('title', 'Panel') · SIPRED</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="sipred-body">
    <div class="app-shell">
        <a class="skip-link" href="#main-content">Saltar al contenido principal</a>
        <button class="sidebar-backdrop" type="button" aria-label="Cerrar menú" data-sidebar-close></button>

        <aside class="sidebar" id="sipred-sidebar" aria-label="Navegación principal">
            <a class="brand-lockup" href="{{ route('dashboard') }}" aria-label="SIPRED, ir al dashboard">
                <span class="brand-logo-frame">
                    <img class="brand-logo" src="{{ asset('assets/branding/sipred.jpeg') }}" alt="Logo SIPRED - Sistema Inteligente de Prevención y Alerta Temprana de Desastres" width="1254" height="1254" decoding="async">
                </span>
                <span class="brand-copy">
                    <span class="brand-name">SIPRED</span>
                    <span class="brand-caption">Sistema Inteligente de Prevención y Alerta Temprana de Desastres</span>
                </span>
            </a>

            <div class="sidebar-section-label">PLATAFORMA</div>
            <nav class="sidebar-nav" aria-label="Plataforma">
                <a class="nav-item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13.2V20h6v-6.8H4Zm10 6.8h6v-9h-6v9ZM4 9.2h6V4H4v5.2ZM14 4v3h6V4h-6Z"/><path d="M12 4h.01M12 12h.01M12 20h.01"/></svg>
                    <span>Dashboard</span>
                    <span class="nav-current-dot" aria-hidden="true"></span>
                </a>
                <a class="nav-item {{ request()->routeIs('riesgos.*') ? 'is-active' : '' }}" href="{{ route('riesgos.index') }}" @if (request()->routeIs('riesgos.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    <span>Mapa de riesgos</span><span class="nav-soon">Próximo</span>
                </a>
                <a class="nav-item {{ request()->routeIs('sensores.*') ? 'is-active' : '' }}" href="{{ route('sensores.index') }}" @if (request()->routeIs('sensores.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="4" width="14" height="16" rx="2"/><path d="M9 8h6M9 12h6M9 16h3"/></svg>
                    <span>Sensores</span>
                </a>
                <a class="nav-item {{ request()->routeIs('mediciones.*') ? 'is-active' : '' }}" href="{{ route('mediciones.index') }}" @if (request()->routeIs('mediciones.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5M4 19h16M7 15l4-5 3 3 5-7"/></svg>
                    <span>Mediciones</span>
                </a>
                <a class="nav-item {{ request()->routeIs('alertas.*') ? 'is-active' : '' }}" href="{{ route('alertas.index') }}" @if (request()->routeIs('alertas.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/></svg>
                    <span>Alertas</span>
                </a>
                <a class="nav-item {{ request()->routeIs('eventos.*') ? 'is-active' : '' }}" href="{{ route('eventos.index') }}" @if (request()->routeIs('eventos.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <span>Eventos</span>
                </a>
                <a class="nav-item {{ request()->routeIs('reportes.*') ? 'is-active' : '' }}" href="{{ route('reportes.index') }}" @if (request()->routeIs('reportes.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg>
                    <span>Reportes</span>
                </a>
            </nav>

            <div class="sidebar-section-label sidebar-section-spaced">ADMINISTRACIÓN</div>
            <nav class="sidebar-nav" aria-label="Administración">
                <a class="nav-item {{ request()->routeIs('usuarios.*') ? 'is-active' : '' }}" href="{{ route('usuarios.index') }}" @if (request()->routeIs('usuarios.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0M16 5.5a3 3 0 0 1 0 5.8M17 15a5 5 0 0 1 3.5 5"/></svg>
                    <span>Usuarios</span>
                </a>
                <a class="nav-item {{ (request()->routeIs('configuracion.*') || request()->routeIs('recovery.settings.*')) ? 'is-active' : '' }}" href="{{ route('configuracion.index') }}" @if (request()->routeIs('configuracion.*') || request()->routeIs('recovery.settings.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.7 1l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.7-1l-1.7.7-1.4-2.4 1.4-1.1a7 7 0 0 1 0-2l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.7-1l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.7 1l1.7-.7 1.4 2.4-1.4 1.1a7 7 0 0 1 0 2Z" transform="translate(-1 -1) scale(.96)"/></svg>
                    <span>Configuración</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="connection-card">
                    <span class="connection-pulse" aria-hidden="true"></span>
                    <div><strong>Plataforma lista</strong><span>Integración IoT pendiente</span></div>
                </div>
            </div>
        </aside>

        <main class="main-content" id="main-content">
            <header class="topbar" aria-label="Barra superior">
                <div class="topbar-left">
                    <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-controls="sipred-sidebar" aria-expanded="false" data-sidebar-toggle>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="breadcrumb">
                        <a class="topbar-brand" href="{{ route('dashboard') }}" aria-label="Ir al dashboard de SIPRED">
                            <img class="topbar-logo" src="{{ asset('assets/branding/sipred.jpeg') }}" alt="Logo SIPRED - Sistema Inteligente de Prevención y Alerta Temprana de Desastres" width="1254" height="1254" decoding="async">
                            <span class="topbar-brand-name">SIPRED</span>
                        </a>
                        <span class="breadcrumb-slash" aria-hidden="true">/</span>
                        <strong>@yield('section', 'Dashboard')</strong>
                    </div>
                </div>
                <div class="topbar-right">
                    <span class="topbar-date">{{ now()->translatedFormat('l, d \d\e F Y') }}</span>
                    <div class="notification-summary" role="status" aria-label="Notificaciones: 0 alertas registradas" title="Sin alertas registradas">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/></svg>
                        <span class="notification-label">Notificaciones</span>
                        <span class="notification-count" aria-hidden="true">0</span>
                    </div>
                    <span class="system-status" aria-label="Entorno local">
                        <span class="status-dot" aria-hidden="true"></span>
                        <span class="status-label">Entorno local</span>
                    </span>
                    <details class="user-menu">
                        <summary class="user-menu-toggle">
                            <span class="user-avatar" aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                            <span class="user-menu-copy"><strong>{{ auth()->user()->name }}</strong><span>Administración local</span></span>
                            <svg class="user-menu-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                        </summary>
                        <div class="user-menu-panel">
                            <a class="user-menu-recovery-link" href="{{ route('account.recovery') }}">Seguridad de la cuenta</a>
                            <div class="user-menu-person"><strong>{{ auth()->user()->name }}</strong><span>Sesión de administración local</span></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="logout-menu-button" type="submit">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            <div class="page-loading-skeleton" data-page-loading-skeleton hidden aria-hidden="true">
                <div class="loading-skeleton-heading"><span></span><span></span></div>
                <div class="loading-skeleton-stats"><span></span><span></span><span></span><span></span></div>
                <div class="loading-skeleton-panels"><span></span><span></span></div>
                <div class="loading-skeleton-table"><span></span><span></span><span></span><span></span></div>
            </div>
            <p class="visually-hidden" data-page-loading-status role="status" aria-live="polite" aria-atomic="true"></p>

            <div class="page-content">
                @yield('content')
            </div>
            <footer class="app-footer"><span>SIPRED · Sistema Inteligente de Prevención y Alerta Temprana de Desastres</span><span>El Espinal, Tolima</span></footer>
        </main>
    </div>
</body>
</html>
