<?php

namespace App\Http\Controllers;

use App\Models\RecoveryCode;
use App\Models\RecoveryFile;
use App\Models\RecoverySetting;
use App\Services\AccountRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountRecoveryController extends Controller
{
    private const GENERIC_ERROR = 'No se pudo comprobar la información. Revisa los datos e inténtalo de nuevo.';

    public function __construct(private readonly AccountRecoveryService $recovery)
    {
    }

    public function showRecovery(): View
    {
        return view('auth.recover', ['settings' => RecoverySetting::current()]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'string', 'max:24'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        if ($this->rateLimited($request, 'code', $email)) {
            return $this->recoveryFailed($email);
        }

        $code = preg_replace('/\s+/', '', $validated['code']) ?? '';
        $currentFormat = preg_match('/^[A-HJ-NP-Za-hj-np-z2-9]{4}-[A-HJ-NP-Za-hj-np-z2-9]{4}$/D', $code) === 1;
        $legacyFormat = preg_match('/^[A-HJ-NP-Za-hj-np-z2-9]{4}(?:-[A-HJ-NP-Za-hj-np-z2-9]{4}){2}$/D', $code) === 1;

        if (! $currentFormat && ! $legacyFormat) {
            return $this->recoveryFailed($email);
        }

        if ($legacyFormat) {
            $code = strtoupper($code);
        }

        $challengeToken = $this->recovery->verifyCode($email, $code);
        if ($challengeToken === null) {
            return $this->recoveryFailed($email);
        }

        $this->storeChallenge($request, $challengeToken);

        return redirect()->route('password.recovery.reset')
            ->with('status', 'Cuenta verificada. Ahora puedes cambiar tu contraseña.');
    }

    public function verifyFile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'recovery_file' => ['required', 'file', 'max:8'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        if ($this->rateLimited($request, 'file', $email)) {
            return $this->recoveryFailed($email);
        }

        $file = $request->file('recovery_file');
        if ($file === null || $file->getSize() > 8192) {
            return $this->recoveryFailed($email);
        }

        $contents = file_get_contents($file->getRealPath());
        if (! is_string($contents)) {
            return $this->recoveryFailed($email);
        }

        $challengeToken = $this->recovery->verifyRecoveryFile($email, $contents);
        unset($contents);

        if ($challengeToken === null) {
            return $this->recoveryFailed($email);
        }

        $this->storeChallenge($request, $challengeToken);

        return redirect()->route('password.recovery.reset')
            ->with('status', 'Cuenta verificada. Ahora puedes cambiar tu contraseña.');
    }

    public function showResetForm(Request $request): View|RedirectResponse
    {
        $token = $request->session()->get(AccountRecoveryService::CHALLENGE_SESSION_KEY);

        if (! is_string($token) || ! $this->recovery->challengeIsValid($token)) {
            $request->session()->forget(AccountRecoveryService::CHALLENGE_SESSION_KEY);

            return redirect()->route('password.recovery')
                ->withErrors(['recovery' => 'La verificación venció o ya se usó. Vuelve a iniciar el proceso.']);
        }

        return view('auth.reset-password');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', 'string', Password::min(12)],
        ]);

        $token = $request->session()->get(AccountRecoveryService::CHALLENGE_SESSION_KEY);
        if (! is_string($token)) {
            return $this->challengeExpired($request);
        }

        $user = $this->recovery->resetPassword($token, $validated['password']);
        if ($user === null) {
            return $this->challengeExpired($request);
        }

        $request->session()->forget(AccountRecoveryService::CHALLENGE_SESSION_KEY);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Tu contraseña se cambió correctamente. Ya puedes iniciar sesión.');
    }

    public function accountRecovery(Request $request): View
    {
        $userId = (int) $request->user()->getKey();
        $codesOwnerId = (int) $request->session()->get('recovery_codes_user_id', 0);
        $newCodes = $codesOwnerId === $userId ? $request->session()->get('recovery_codes_once', []) : [];
        $request->session()->forget(['recovery_codes_once', 'recovery_codes_user_id']);

        return view('auth.account-recovery', array_merge(
            $this->recovery->credentialsSummary($request->user()),
            [
                'settings' => RecoverySetting::current(),
                'newCodes' => is_array($newCodes) ? $newCodes : [],
            ],
        ));
    }

    public function generateCodes(Request $request): RedirectResponse
    {
        if (! RecoverySetting::current()->recovery_codes_enabled) {
            return back()->withErrors(['recovery' => 'Los códigos de respaldo no están disponibles. Contacta al administrador de SIPRED.']);
        }

        if (! $this->allowCredentialGeneration($request, 'codes')) {
            return back()->withErrors(['recovery' => 'Espera unos segundos antes de generar otro conjunto de códigos.']);
        }

        $userId = $request->user()->getKey();
        $codes = $this->recovery->generateCodes($request->user());
        $destination = $request->input('return_to') === 'configuracion.index'
            ? 'configuracion.index'
            : 'account.recovery';

        return redirect()->route($destination)
            ->with('recovery_codes_once', $codes)
            ->with('recovery_codes_user_id', $userId)
            ->with('status', 'Se generaron seis códigos. Descárgalos o guárdalos ahora; no podrás consultarlos otra vez.');
    }

    public function downloadRecoveryFile(Request $request)
    {
        if (! RecoverySetting::current()->recovery_file_enabled) {
            return back()->withErrors(['recovery' => 'El archivo de respaldo no está disponible. Contacta al administrador de SIPRED.']);
        }

        if (! $this->allowCredentialGeneration($request, 'file')) {
            return back()->withErrors(['recovery' => 'Espera unos segundos antes de generar otro archivo de respaldo.']);
        }

        $contents = $this->recovery->generateRecoveryFile($request->user());

        return response()->streamDownload(
            static function () use ($contents): void {
                echo $contents;
            },
            'sipred-recovery.json',
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function allowCredentialGeneration(Request $request, string $method): bool
    {
        $key = 'sipred:account-recovery:generation:'.$method.':'.$request->user()->getKey();

        return RateLimiter::attempt($key, 1, static fn (): bool => true, 5);
    }

    private function rateLimited(Request $request, string $method, string $email): bool
    {
        $key = 'sipred:password-recovery:'.$method.':'.$request->ip().':'.hash('sha256', $email);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return true;
        }

        RateLimiter::hit($key, 600);

        return false;
    }

    private function recoveryFailed(string $email): RedirectResponse
    {
        return redirect()->route('password.recovery')
            ->withInput(['email' => $email])
            ->withErrors(['recovery' => self::GENERIC_ERROR]);
    }

    private function challengeExpired(Request $request): RedirectResponse
    {
        $request->session()->forget(AccountRecoveryService::CHALLENGE_SESSION_KEY);

        return redirect()->route('password.recovery')
            ->withErrors(['recovery' => 'La verificación venció o ya se usó. Vuelve a iniciar el proceso.']);
    }

    private function storeChallenge(Request $request, string $token): void
    {
        $request->session()->regenerate();
        $request->session()->put(AccountRecoveryService::CHALLENGE_SESSION_KEY, $token);
    }
}