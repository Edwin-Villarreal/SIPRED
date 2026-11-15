<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\RecoverySetting;
use App\Services\AccountRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RecoverySettingsController extends Controller
{
    public function __construct(private readonly AccountRecoveryService $recovery)
    {
    }
    public function edit(Request $request): View
    {
        $this->authorizeSettings($request);

        $codesOwnerId = (int) $request->session()->get('recovery_codes_user_id', 0);
        $newCodes = $codesOwnerId === (int) $request->user()->getKey()
            ? $request->session()->get('recovery_codes_once', [])
            : [];
        $request->session()->forget(['recovery_codes_once', 'recovery_codes_user_id']);

        return view('settings.recovery', array_merge(
            $this->recovery->credentialsSummary($request->user()),
            [
                'settings' => RecoverySetting::current(),
                'newCodes' => is_array($newCodes) ? $newCodes : [],
            ],
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeSettings($request);

        if ($request->exists('password_reset_enabled')) {
            return back()->withErrors([
                'password_reset_enabled' => 'La opción para cambiar la contraseña siempre debe permanecer activa.',
            ]);
        }

        $validated = $request->validate([
            'recovery_codes_enabled' => ['required', 'in:0,1'],
            'recovery_file_enabled' => ['required', 'in:0,1'],
        ]);

        $codesEnabled = $validated['recovery_codes_enabled'] === '1';
        $fileEnabled = $validated['recovery_file_enabled'] === '1';

        if (! $codesEnabled && ! $fileEnabled) {
            return back()->withErrors([
                'recovery_methods' => 'Mantén activo al menos un método de respaldo para no perder el acceso a las cuentas.',
            ])->withInput();
        }

        DB::transaction(function () use ($request, $codesEnabled, $fileEnabled): void {
            $settings = RecoverySetting::query()
                ->whereKey(1)
                ->lockForUpdate()
                ->firstOrFail();

            $settings->forceFill([
                'recovery_codes_enabled' => $codesEnabled,
                'recovery_file_enabled' => $fileEnabled,
            ])->save();

            Evento::query()->create([
                'usuario_id' => $request->user()->getKey(),
                'tipo' => 'seguridad',
                'titulo' => 'Configuración de recuperación actualizada',
                'descripcion' => 'Se actualizaron los métodos habilitados para recuperar cuentas.',
                'nivel' => 'informativa',
                'origen' => 'sistema',
                'ocurrio_at' => now(),
            ]);
        });

        return redirect()->route('recovery.settings.edit')
            ->with('status', 'Los cambios se guardaron correctamente.');
    }

    private function authorizeSettings(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('recovery.settings'), 403);
    }
}