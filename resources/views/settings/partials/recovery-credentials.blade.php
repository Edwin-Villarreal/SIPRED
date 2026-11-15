@if (session('status'))
    <div class="recovery-admin-message" role="status">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="recovery-admin-error" role="alert">{{ $errors->first() }}</div>
@endif

<section class="recovery-account-section" id="recovery-settings-section" aria-labelledby="recovery-account-title">
    <div class="recovery-account-heading">
        <span class="panel-kicker">SEGURIDAD DE LA CUENTA</span>
        <h2 id="recovery-account-title">Recuperación de cuenta</h2>
        <p>Administra tus propios elementos de respaldo. Los códigos y el archivo solo se muestran o descargan al generarlos.</p>
    </div>

    @if (count($newCodes) === 6)
        <section class="panel recovery-codes-panel" aria-labelledby="codes-title">
            <div class="panel-heading"><div><h2 id="codes-title">Códigos de respaldo</h2></div></div>
            <p class="recovery-warning">Guarda estos códigos antes de salir. Por seguridad, no podrás volver a consultarlos desde aquí.</p>
            <ol class="recovery-code-list" data-recovery-code-list>
                @foreach ($newCodes as $code)
                    <li><code data-recovery-code>{{ $code }}</code></li>
                @endforeach
            </ol>
            <div class="recovery-actions">
                <button class="btn-sipred" type="button" data-copy-recovery-codes>Copiar códigos</button>
                <button class="recovery-secondary-button" type="button" data-download-recovery-codes>Descargar copia</button>
                <span class="recovery-feedback" data-recovery-feedback role="status" aria-live="polite"></span>
            </div>
        </section>
    @endif

    <div class="recovery-method-grid" aria-label="Elementos personales de recuperación">
        <section class="panel recovery-method-card" aria-labelledby="recovery-codes-title">
            <div class="panel-heading">
                <div><h2 id="recovery-codes-title">Códigos de respaldo</h2></div>
                <span class="recovery-count">{{ $availableCodes }} {{ $availableCodes === 1 ? 'disponible' : 'disponibles' }}</span>
            </div>
            <p>Guarda estos códigos para recuperar tu cuenta si no puedes iniciar sesión. Cada código se utiliza una sola vez.</p>
            <p class="recovery-meta"><strong>Última generación:</strong>
                @if ($lastCodesGeneratedAt)
                    <time datetime="{{ $lastCodesGeneratedAt->toIso8601String() }}">{{ $lastCodesGeneratedAt->format('d/m/Y H:i') }}</time>
                @else
                    Sin registro
                @endif
            </p>
            @if ($settings->recovery_codes_enabled)
                <form method="POST" action="{{ route('account.recovery.codes') }}" data-recovery-generation-form>
                    @csrf
                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    <button class="btn-sipred" type="submit">Generar y descargar códigos</button>
                </form>
            @else
                <span class="recovery-disabled-label">Desactivado por configuración. No se pueden generar ni usar códigos.</span>
            @endif
            <p class="recovery-credential-note">Los códigos originales no se pueden recuperar desde SIPRED. Si no los guardaste o ya no quedan disponibles, genera un conjunto nuevo.</p>
        </section>

        <section class="panel recovery-method-card" aria-labelledby="recovery-file-title">
            <div class="panel-heading">
                <div><h2 id="recovery-file-title">Archivo de respaldo</h2></div>
                <span class="recovery-count">{{ $availableFiles > 0 ? 'Disponible' : 'Sin archivo vigente' }}</span>
            </div>
            <p>Descarga un archivo personal que puedes utilizar para recuperar el acceso a tu cuenta.</p>
            <p class="recovery-meta"><strong>Estado de la credencial:</strong> {{ $availableFiles > 0 ? 'Disponible para usar una vez' : 'Requiere generar un archivo nuevo' }}</p>
            <p class="recovery-meta"><strong>Última generación:</strong>
                @if ($lastFileGeneratedAt)
                    <time datetime="{{ $lastFileGeneratedAt->toIso8601String() }}">{{ $lastFileGeneratedAt->format('d/m/Y H:i') }}</time>
                @else
                    Sin registro
                @endif
            </p>
            @if (! $settings->recovery_file_enabled)
                <span class="recovery-disabled-label">Desactivado por configuración. No se puede generar ni usar el archivo.</span>
            @else
                <form method="POST" action="{{ route('account.recovery.file') }}" data-recovery-file-form>
                    @csrf
                    <button class="btn-sipred" type="submit">Generar y descargar archivo</button>
                </form>
                @if ($availableFiles === 0 && $hasRecoveryFileHistory)
                    <p class="recovery-credential-note">El archivo anterior ya se utilizó o dejó de estar vigente. Genera uno nuevo para volver a recuperar el acceso.</p>
                @else
                    <p class="recovery-credential-note">El archivo contiene una credencial privada. Guárdalo en un lugar seguro; SIPRED solo entrega la credencial al generarlo.</p>
                @endif
            @endif
        </section>
    </div>
</section>