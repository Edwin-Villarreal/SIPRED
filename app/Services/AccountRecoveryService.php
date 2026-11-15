<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\Evento;
use App\Models\PasswordRecoveryChallenge;
use App\Models\RecoveryCode;
use App\Models\RecoveryFile;
use App\Models\RecoverySetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AccountRecoveryService
{
    public const CHALLENGE_SESSION_KEY = 'sipred.password_recovery_challenge';

    public const CHALLENGE_LIFETIME_MINUTES = 10;

    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';

    public function credentialsSummary(User $user): array
    {
        $userId = $user->getKey();
        $latestCode = RecoveryCode::query()->where('user_id', $userId)->latest('generated_at')->first();
        $latestFile = RecoveryFile::query()->where('user_id', $userId)->latest('generated_at')->first();

        return [
            'availableCodes' => RecoveryCode::query()
                ->where('user_id', $userId)
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->count(),
            'availableFiles' => RecoveryFile::query()
                ->where('user_id', $userId)
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->count(),
            'lastCodesGeneratedAt' => $latestCode?->generated_at,
            'lastFileGeneratedAt' => $latestFile?->generated_at,
            'hasRecoveryFileHistory' => $latestFile !== null,
        ];
    }

    public function generateCodes(User $user): array
    {
        $codes = [];

        while (count($codes) < 6) {
            $characters = '';

            for ($index = 0; $index < 8; $index++) {
                $characters .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }

            $code = substr($characters, 0, 4).'-'.substr($characters, 4, 4);

            if (! in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        DB::transaction(function () use ($user, $codes): void {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            RecoveryCode::query()
                ->where('user_id', $user->getKey())
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            foreach ($codes as $code) {
                RecoveryCode::query()->create([
                    'user_id' => $user->getKey(),
                    'code_hash' => Hash::make($code),
                    'generated_at' => now(),
                ]);
            }

            $this->recordSecurityEvent($user, 'Generación de códigos de recuperación', 'Se generó un nuevo conjunto de códigos de recuperación.');
        });

        return $codes;
    }

    public function generateRecoveryFile(User $user): string
    {
        $recoveryId = (string) Str::uuid();
        $credential = bin2hex(random_bytes(32));
        $generatedAt = now();

        DB::transaction(function () use ($user, $recoveryId, $credential, $generatedAt): void {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            RecoveryFile::query()
                ->where('user_id', $user->getKey())
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => $generatedAt]);

            RecoveryFile::query()->create([
                'user_id' => $user->getKey(),
                'recovery_id' => $recoveryId,
                'credential_hash' => Hash::make($credential),
                'generated_at' => $generatedAt,
            ]);

            $this->recordSecurityEvent($user, 'Generación de archivo de recuperación', 'Se generó un archivo oficial de recuperación.');
        });

        return json_encode([
            'format' => 'sipred-recovery',
            'version' => 1,
            'recovery_id' => $recoveryId,
            'credential' => $credential,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    }
    public function verifyCode(string $email, string $code): ?string
    {
        if (! RecoverySetting::current()->recovery_codes_enabled) {
            return null;
        }

        return DB::transaction(function () use ($email, $code): ?string {
            $user = User::query()
                ->where('email', mb_strtolower(trim($email)))
                ->where('status', User::STATUS_ACTIVE)
                ->first();

            if ($user === null) {
                return null;
            }

            $availableCodes = RecoveryCode::query()
                ->where('user_id', $user->getKey())
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->get();

            foreach ($availableCodes as $recoveryCode) {
                if (Hash::check($code, $recoveryCode->code_hash)) {
                    return $this->createChallenge($user, 'code', $recoveryCode->getKey(), null);
                }
            }

            return null;
        });
    }

    public function verifyRecoveryFile(string $email, string $contents): ?string
    {
        if (! RecoverySetting::current()->recovery_file_enabled || strlen($contents) > 8192) {
            return null;
        }

        try {
            $payload = json_decode($contents, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($payload)
            || count($payload) !== 4
            || array_diff(['format', 'version', 'recovery_id', 'credential'], array_keys($payload)) !== []
            || $payload['format'] !== 'sipred-recovery'
            || ! is_int($payload['version'])
            || $payload['version'] !== 1
            || ! is_string($payload['recovery_id'])
            || ! Str::isUuid($payload['recovery_id'])
            || ! is_string($payload['credential'])
            || ! preg_match('/^[a-f0-9]{64}$/D', $payload['credential'])) {
            return null;
        }

        return DB::transaction(function () use ($email, $payload): ?string {
            $user = User::query()
                ->where('email', mb_strtolower(trim($email)))
                ->where('status', User::STATUS_ACTIVE)
                ->first();

            if ($user === null) {
                return null;
            }

            $recoveryFile = RecoveryFile::query()
                ->where('user_id', $user->getKey())
                ->where('recovery_id', $payload['recovery_id'])
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->first();

            if ($recoveryFile === null || ! Hash::check($payload['credential'], $recoveryFile->credential_hash)) {
                return null;
            }

            return $this->createChallenge($user, 'file', null, $recoveryFile->getKey());
        });
    }

    public function resetPassword(string $challengeToken, string $newPassword): ?User
    {
        if (! preg_match('/^[a-f0-9]{64}$/D', $challengeToken)) {
            return null;
        }

        return DB::transaction(function () use ($challengeToken, $newPassword): ?User {
            $challenge = PasswordRecoveryChallenge::query()
                ->where('token_hash', hash('sha256', $challengeToken))
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($challenge === null) {
                return null;
            }

            $settings = RecoverySetting::current();
            if (($challenge->method === 'code' && ! $settings->recovery_codes_enabled)
                || ($challenge->method === 'file' && ! $settings->recovery_file_enabled)) {
                return null;
            }

            $user = User::query()
                ->whereKey($challenge->user_id)
                ->where('status', User::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if ($user === null) {
                return null;
            }

            if ($challenge->method === 'code'
                && $challenge->recovery_code_id !== null
                && $challenge->recovery_file_id === null) {
                $credential = RecoveryCode::query()
                    ->whereKey($challenge->recovery_code_id)
                    ->where('user_id', $user->getKey())
                    ->whereNull('used_at')
                    ->whereNull('revoked_at')
                    ->lockForUpdate()
                    ->first();

                if ($credential === null) {
                    return null;
                }

                $credential->forceFill(['used_at' => now()])->save();
            } elseif ($challenge->method === 'file'
                && $challenge->recovery_file_id !== null
                && $challenge->recovery_code_id === null) {
                $credential = RecoveryFile::query()
                    ->whereKey($challenge->recovery_file_id)
                    ->where('user_id', $user->getKey())
                    ->whereNull('used_at')
                    ->whereNull('revoked_at')
                    ->lockForUpdate()
                    ->first();

                if ($credential === null) {
                    return null;
                }

                $credential->forceFill(['used_at' => now()])->save();
            } else {
                return null;
            }

            $user->forceFill([
                'password' => Hash::make($newPassword),
                'remember_token' => Str::random(60),
            ])->save();

            $challenge->forceFill(['consumed_at' => now()])->save();

            PasswordRecoveryChallenge::query()
                ->where('user_id', $user->getKey())
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            $this->invalidateDatabaseSessions($user);

            $methodLabel = $challenge->method === 'code' ? 'código' : 'archivo';
            $this->recordSecurityEvent(
                $user,
                'Restablecimiento de contraseña',
                'Contraseña restablecida mediante '.$methodLabel.' de recuperación.',
            );

            return $user;
        });
    }

    public function challengeIsValid(string $challengeToken): bool
    {
        if (! preg_match('/^[a-f0-9]{64}$/D', $challengeToken)) {
            return false;
        }

        $challenge = PasswordRecoveryChallenge::query()
            ->where('token_hash', hash('sha256', $challengeToken))
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($challenge === null) {
            return false;
        }

        $settings = RecoverySetting::current();

        return $challenge->method === 'code'
            ? $settings->recovery_codes_enabled
            : ($challenge->method === 'file' && $settings->recovery_file_enabled);
    }

    private function createChallenge(User $user, string $method, ?int $codeId, ?int $fileId): string
    {
        $token = bin2hex(random_bytes(32));

        PasswordRecoveryChallenge::query()
            ->where('user_id', $user->getKey())
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        PasswordRecoveryChallenge::query()->create([
            'user_id' => $user->getKey(),
            'method' => $method,
            'recovery_code_id' => $codeId,
            'recovery_file_id' => $fileId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(self::CHALLENGE_LIFETIME_MINUTES),
        ]);

        return $token;
    }

    private function invalidateDatabaseSessions(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $connection = config('session.connection') ?: config('database.default');
        $table = (string) config('session.table', 'sessions');

        if (! Schema::connection($connection)->hasTable($table)) {
            return;
        }

        DB::connection($connection)
            ->table($table)
            ->where('user_id', $user->getKey())
            ->delete();
    }

    private function recordSecurityEvent(User $user, string $title, string $description): void
    {
        Evento::query()->create([
            'usuario_id' => $user->getKey(),
            'tipo' => 'seguridad',
            'titulo' => $title,
            'descripcion' => $description,
            'nivel' => Alerta::LEVEL_INFORMATIONAL,
            'origen' => 'sistema',
            'ocurrio_at' => now(),
        ]);
    }
}