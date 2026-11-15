<section class="panel recovery-settings-panel" id="recovery-method-settings" aria-labelledby="recovery-method-settings-title">
    <div class="panel-heading recovery-settings-heading">
        <div>
            <span class="panel-kicker">ADMINISTRACIÓN</span>
            <h2 id="recovery-method-settings-title">Métodos de recuperación habilitados</h2>
            <p class="recovery-section-description">Estas opciones se aplican a todas las cuentas. El cambio de contraseña permanece activado.</p>
        </div>
    </div>

    @php
        $codesEnabled = (string) old('recovery_codes_enabled', $settings->recovery_codes_enabled ? '1' : '0') === '1';
        $fileEnabled = (string) old('recovery_file_enabled', $settings->recovery_file_enabled ? '1' : '0') === '1';
    @endphp

    @if ($canManage)
        <form method="POST" action="{{ route('recovery.settings.update') }}" class="recovery-settings-form">
            @csrf
            @method('PUT')
    @endif

    <div class="recovery-setting-row">
        <div class="recovery-setting-copy">
            <strong id="recovery-codes-setting-label">Códigos de respaldo</strong>
            <span>Permite activar o desactivar la recuperación mediante códigos de respaldo.</span>
        </div>
        @if ($canManage)
            <div class="recovery-setting-control">
                <input type="hidden" name="recovery_codes_enabled" value="0">
                <label class="recovery-switch" for="recovery_codes_enabled">
                    <input id="recovery_codes_enabled" type="checkbox" role="switch" name="recovery_codes_enabled" value="1" aria-labelledby="recovery-codes-setting-label" @checked($codesEnabled)>
                    <span class="recovery-switch-track" aria-hidden="true"></span>
                </label>
            </div>
        @else
            <span class="recovery-fixed-state">{{ $codesEnabled ? 'Activado' : 'Desactivado' }}</span>
        @endif
    </div>

    <div class="recovery-setting-row">
        <div class="recovery-setting-copy">
            <strong id="recovery-file-setting-label">Archivo de respaldo</strong>
            <span>Permite activar o desactivar la recuperación mediante archivo de respaldo.</span>
        </div>
        @if ($canManage)
            <div class="recovery-setting-control">
                <input type="hidden" name="recovery_file_enabled" value="0">
                <label class="recovery-switch" for="recovery_file_enabled">
                    <input id="recovery_file_enabled" type="checkbox" role="switch" name="recovery_file_enabled" value="1" aria-labelledby="recovery-file-setting-label" @checked($fileEnabled)>
                    <span class="recovery-switch-track" aria-hidden="true"></span>
                </label>
            </div>
        @else
            <span class="recovery-fixed-state">{{ $fileEnabled ? 'Activado' : 'Desactivado' }}</span>
        @endif
    </div>

    <div class="recovery-setting-row recovery-setting-fixed">
        <div class="recovery-setting-copy">
            <strong id="recovery-password-label">Cambio de contraseña</strong>
            <span>Permite establecer una contraseña nueva después de verificar correctamente la identidad del usuario.</span>
        </div>
        <span class="recovery-fixed-state">Activado</span>
    </div>

    @if ($canManage)
        <p class="recovery-warning">Mantén activo al menos un método de respaldo para no perder el acceso a las cuentas.</p>
        <div class="recovery-actions">
            <button class="btn-sipred" type="submit">Guardar configuración</button>
            @if ($standalone ?? false)
                <a class="recovery-secondary-link" href="{{ route('configuracion.index') }}">Volver a configuración</a>
            @endif
        </div>
        </form>
    @endif
</section>