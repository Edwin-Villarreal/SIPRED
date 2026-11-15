<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Services\AccountRecoveryService;
use App\Models\PasswordRecoveryChallenge;
use App\Models\Permission;
use App\Models\RecoveryCode;
use App\Models\RecoveryFile;
use App\Models\RecoverySetting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RecoverySettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_six_unique_hashed_codes_and_displays_them_only_once(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('account.recovery.codes'))
            ->assertRedirect(route('account.recovery'))
            ->assertSessionHas('recovery_codes_once');

        $codes = session()->get('recovery_codes_once');
        $this->assertCount(6, $codes);
        $this->assertCount(6, array_unique($codes));
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[A-HJ-NP-Za-hj-np-z2-9]{4}-[A-HJ-NP-Za-hj-np-z2-9]{4}$/', $code);
            $this->assertTrue(RecoveryCode::query()->where('user_id', $user->getKey())->get()
                ->contains(fn (RecoveryCode $record): bool => Hash::check($code, $record->code_hash)));
        }
        $this->assertDatabaseCount('recovery_codes', 6);
        $this->assertDatabaseMissing('recovery_codes', ['code_hash' => $codes[0]]);

        $this->get(route('account.recovery'))->assertOk()->assertSee($codes[0]);
        $this->get(route('account.recovery'))->assertOk()->assertDontSee($codes[0]);
    }

    public function test_regenerating_codes_revokes_all_previous_available_codes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('account.recovery.codes'))->assertRedirect();
        $oldCodes = session()->get('recovery_codes_once');

        RateLimiter::clear('sipred:account-recovery:generation:codes:'.$user->getKey());
        $this->post(route('account.recovery.codes'))->assertRedirect();
        $newCodes = session()->get('recovery_codes_once');

        $this->assertCount(6, $oldCodes);
        $this->assertCount(6, $newCodes);
        $this->assertCount(6, RecoveryCode::query()->where('user_id', $user->getKey())->whereNotNull('revoked_at')->get());
        $this->assertCount(6, RecoveryCode::query()->where('user_id', $user->getKey())->whereNull('revoked_at')->whereNull('used_at')->get());
        $this->assertFalse(RecoveryCode::query()->where('user_id', $user->getKey())->get()
            ->contains(fn (RecoveryCode $record): bool => Hash::check($oldCodes[0], $record->code_hash) && $record->revoked_at === null));
    }

    public function test_codes_generated_from_configuration_are_shown_and_can_be_downloaded_once(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('configuracion.index'))
            ->post(route('account.recovery.codes'), ['return_to' => 'configuracion.index'])
            ->assertRedirect(route('configuracion.index'))
            ->assertSessionHas('recovery_codes_once');

        $codes = session()->get('recovery_codes_once');
        $this->assertCount(6, $codes);

        $this->get(route('configuracion.index'))
            ->assertOk()
            ->assertSee($codes[0])
            ->assertSeeText('Generar y descargar códigos')
            ->assertSeeText('Descargar copia')
            ->assertSeeText('Última generación:');

        $this->get(route('configuracion.index'))
            ->assertOk()
            ->assertDontSee($codes[0]);
    }

    public function test_code_generation_uses_the_short_format_and_new_codes_verify_without_breaking_legacy_codes(): void
    {
        $user = User::factory()->create();
        $codes = app(AccountRecoveryService::class)->generateCodes($user);

        $this->assertCount(6, $codes);
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[A-HJ-NP-Za-hj-np-z2-9]{4}-[A-HJ-NP-Za-hj-np-z2-9]{4}$/', $code);
        }

        $this->actingAs($user)->post(route('logout'));
        $this->post(route('password.recovery.code'), [
            'email' => $user->email,
            'code' => $codes[0],
        ])->assertRedirect(route('password.recovery.reset'));

        $legacyUser = User::factory()->create();
        $this->createCode($legacyUser, 'ABCD-EFGH-JKLM');
        $this->post(route('password.recovery.code'), [
            'email' => $legacyUser->email,
            'code' => 'abcd-efgh-jklm',
        ])->assertRedirect(route('password.recovery.reset'));
    }

    public function test_duplicate_code_generation_request_does_not_replace_the_first_set(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('account.recovery.codes'))->assertRedirect();
        $this->post(route('account.recovery.codes'))->assertSessionHasErrors('recovery');

        $this->assertDatabaseCount('recovery_codes', 6);
    }

    public function test_configuration_shows_only_the_signed_in_users_credential_summary(): void
    {
        $owner = User::factory()->create();
        $codes = app(AccountRecoveryService::class)->generateCodes($owner);
        $anotherUser = User::factory()->create();

        $this->actingAs($anotherUser)
            ->get(route('configuracion.index'))
            ->assertOk()
            ->assertDontSee($codes[0])
            ->assertSeeText('0 disponibles')
            ->assertSeeText('Sin archivo vigente');

        $this->assertDatabaseCount('recovery_codes', 6);
        $this->assertDatabaseHas('recovery_codes', ['user_id' => $owner->getKey()]);
        $this->assertDatabaseMissing('recovery_codes', ['user_id' => $anotherUser->getKey()]);
    }
    public function test_configuration_does_not_show_one_users_new_codes_to_another_user(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)
            ->post(route('account.recovery.codes'), ['return_to' => 'configuracion.index'])
            ->assertRedirect(route('configuracion.index'));

        $codes = session()->get('recovery_codes_once');
        $this->assertCount(6, $codes);

        $anotherUser = User::factory()->create();
        $this->actingAs($anotherUser)
            ->get(route('configuracion.index'))
            ->assertOk()
            ->assertDontSee($codes[0])
            ->assertSeeText('0 disponibles');

        $this->actingAs($owner)
            ->get(route('configuracion.index'))
            ->assertOk()
            ->assertDontSee($codes[0]);
    }
    public function test_valid_code_resets_password_hashes_it_and_can_be_used_only_once(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Old SIPRED password 2026!')]);
        $code = 'ABCD-EFGH-JKLM';
        $record = $this->createCode($user, $code);

        $this->post(route('password.recovery.code'), ['email' => $user->email, 'code' => $code])
            ->assertRedirect(route('password.recovery.reset'));

        $token = session()->get('sipred.password_recovery_challenge');
        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));
        $this->assertDatabaseMissing('password_recovery_challenges', ['token_hash' => $token]);
        $this->get(route('password.recovery.reset'))->assertOk();

        $newPassword = 'Nueva-Segura-SIPRED-2026!';
        $this->post(route('password.recovery.update'), [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
        $this->assertNotSame($newPassword, $user->fresh()->password);
        $this->assertNotNull($record->fresh()->used_at);
        $this->assertDatabaseHas('password_recovery_challenges', ['consumed_at' => now()->toDateTimeString()]);
        $this->assertDatabaseHas('eventos', [
            'usuario_id' => $user->getKey(),
            'tipo' => 'seguridad',
            'titulo' => 'Restablecimiento de contraseña',
        ]);

        $this->post(route('password.recovery.code'), ['email' => $user->email, 'code' => $code])
            ->assertRedirect(route('password.recovery'))
            ->assertSessionHasErrors('recovery');

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Old SIPRED password 2026!'])
            ->assertSessionHasErrors('email');
        $this->post(route('login'), ['email' => $user->email, 'password' => $newPassword])
            ->assertRedirect(route('dashboard'));
    }

    public function test_invalid_code_does_not_create_a_challenge_or_reveal_account_existence(): void
    {
        $user = User::factory()->create();
        $this->createCode($user, 'ABCD-EFGH-JKLM');

        $this->post(route('password.recovery.code'), [
            'email' => $user->email,
            'code' => 'XXXX-YYYY-ZZZZ',
        ])->assertRedirect(route('password.recovery'))
            ->assertSessionHasErrors('recovery');

        $this->assertDatabaseCount('password_recovery_challenges', 0);
        $this->assertDatabaseCount('password_recovery_challenges', 0);

        $this->post(route('password.recovery.code'), [
            'email' => 'no-existe@sipred.local',
            'code' => 'XXXX-YYYY-ZZZZ',
        ])->assertRedirect(route('password.recovery'))
            ->assertSessionHasErrors('recovery');
    }

    public function test_challenge_expires_and_reset_form_requires_prior_verification(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Old SIPRED password 2026!')]);
        $this->get(route('password.recovery.reset'))
            ->assertRedirect(route('password.recovery'));

        $this->createCode($user, 'ABCD-EFGH-JKLM');
        $this->post(route('password.recovery.code'), ['email' => $user->email, 'code' => 'ABCD-EFGH-JKLM'])
            ->assertRedirect(route('password.recovery.reset'));

        $this->travel(11)->minutes();
        $this->get(route('password.recovery.reset'))->assertRedirect(route('password.recovery'));
        $newPassword = 'Nueva-Segura-SIPRED-2026!';
        $this->post(route('password.recovery.update'), [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect(route('password.recovery'));
        $this->assertTrue(Hash::check('Old SIPRED password 2026!', $user->fresh()->password));
    }

    public function test_recovery_file_download_contains_official_json_and_database_stores_only_hash(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('account.recovery.file'));
        $response->assertDownload('sipred-recovery.json');

        $payload = json_decode($response->streamedContent(), true, 16, JSON_THROW_ON_ERROR);
        $this->assertSame('sipred-recovery', $payload['format']);
        $this->assertSame(1, $payload['version']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $payload['credential']);

        $record = RecoveryFile::query()->where('user_id', $user->getKey())->firstOrFail();
        $this->assertSame($payload['recovery_id'], $record->recovery_id);
        $this->assertTrue(Hash::check($payload['credential'], $record->credential_hash));
        $this->assertNotSame($payload['credential'], $record->credential_hash);
        $this->assertDatabaseMissing('recovery_files', ['credential_hash' => $payload['credential']]);
    }

    public function test_regenerating_recovery_file_revokes_the_previous_credential(): void
    {
        $user = User::factory()->create();
        $firstResponse = $this->actingAs($user)->post(route('account.recovery.file'));
        $firstPayload = json_decode($firstResponse->streamedContent(), true, 16, JSON_THROW_ON_ERROR);
        $firstRecord = RecoveryFile::query()->where('user_id', $user->getKey())->firstOrFail();

        RateLimiter::clear('sipred:account-recovery:generation:file:'.$user->getKey());
        $secondResponse = $this->post(route('account.recovery.file'));
        $secondPayload = json_decode($secondResponse->streamedContent(), true, 16, JSON_THROW_ON_ERROR);

        $this->assertNotSame($firstPayload['recovery_id'], $secondPayload['recovery_id']);
        $this->assertNotNull($firstRecord->fresh()->revoked_at);
        $this->assertDatabaseCount('recovery_files', 2);
        $this->assertSame(1, RecoveryFile::query()->where('user_id', $user->getKey())->whereNull('used_at')->whereNull('revoked_at')->count());
    }
    public function test_valid_recovery_file_resets_password_and_used_file_cannot_be_reused(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Old SIPRED password 2026!')]);
        [$payload, $record] = $this->createFileCredential($user);

        $this->post(route('password.recovery.file'), [
            'email' => $user->email,
            'recovery_file' => UploadedFile::fake()->createWithContent('sipred-recovery.json', json_encode($payload)),
        ])->assertRedirect(route('password.recovery.reset'));

        $newPassword = 'Nueva-Segura-SIPRED-2026!';
        $this->post(route('password.recovery.update'), [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
        $this->assertNotNull($record->fresh()->used_at);

        $this->post(route('password.recovery.file'), [
            'email' => $user->email,
            'recovery_file' => UploadedFile::fake()->createWithContent('sipred-recovery.json', json_encode($payload)),
        ])->assertRedirect(route('password.recovery'))
            ->assertSessionHasErrors('recovery');
    }

    public function test_arbitrary_and_oversized_files_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.recovery.file'), [
            'email' => $user->email,
            'recovery_file' => UploadedFile::fake()->createWithContent('anything.json', '{"not":"official"}'),
        ])->assertRedirect(route('password.recovery'))
            ->assertSessionHasErrors('recovery');

        $this->from(route('password.recovery'))->post(route('password.recovery.file'), [
            'email' => $user->email,
            'recovery_file' => UploadedFile::fake()->createWithContent('large.json', str_repeat('x', 8193)),
        ])->assertRedirect(route('password.recovery'))
            ->assertSessionHasErrors('recovery_file');

        $this->assertDatabaseCount('password_recovery_challenges', 0);
    }

    public function test_disabled_recovery_methods_reject_verification_and_keep_records(): void
    {
        $user = User::factory()->create();
        $code = $this->createCode($user, 'ABCD-EFGH-JKLM');
        [$payload, $fileRecord] = $this->createFileCredential($user);
        RecoverySetting::current()->forceFill([
            'recovery_codes_enabled' => false,
            'recovery_file_enabled' => true,
        ])->save();

        $this->post(route('password.recovery.code'), ['email' => $user->email, 'code' => 'ABCD-EFGH-JKLM'])
            ->assertRedirect(route('password.recovery'))
            ->assertSessionHasErrors('recovery');
        $this->assertNull($code->fresh()->revoked_at);
        $this->assertNull($code->fresh()->used_at);

        RecoverySetting::current()->forceFill([
            'recovery_codes_enabled' => true,
            'recovery_file_enabled' => false,
        ])->save();

        $this->post(route('password.recovery.file'), [
            'email' => $user->email,
            'recovery_file' => UploadedFile::fake()->createWithContent('sipred-recovery.json', json_encode($payload)),
        ])->assertRedirect(route('password.recovery'))
            ->assertSessionHasErrors('recovery');
        $this->assertNull($fileRecord->fresh()->revoked_at);
        $this->assertNull($fileRecord->fresh()->used_at);
    }

    public function test_expired_or_used_challenge_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $code = $this->createCode($user, 'ABCD-EFGH-JKLM');

        $this->post(route('password.recovery.code'), ['email' => $user->email, 'code' => 'ABCD-EFGH-JKLM'])
            ->assertRedirect(route('password.recovery.reset'));
        $newPassword = 'Nueva-Segura-SIPRED-2026!';
        $this->post(route('password.recovery.update'), [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect(route('login'));

        $this->assertNotNull($code->fresh()->used_at);
        $this->post(route('password.recovery.update'), [
            'password' => 'Otra-Nueva-Segura-2026!',
            'password_confirmation' => 'Otra-Nueva-Segura-2026!',
        ])->assertRedirect(route('password.recovery'));
        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    }

    public function test_recovery_settings_require_permission_preserve_one_method_and_never_disable_reset(): void
    {
        $admin = User::factory()->create();
        $this->grantRecoverySettings($admin);
        $this->actingAs($admin);
        $settings = RecoverySetting::current();

        $this->put(route('recovery.settings.update'), [
            'recovery_codes_enabled' => '0',
            'recovery_file_enabled' => '1',
        ])->assertRedirect(route('recovery.settings.edit'));
        $this->assertDatabaseHas('recovery_settings', [
            'id' => 1,
            'recovery_codes_enabled' => 0,
            'recovery_file_enabled' => 1,
        ]);

        $this->put(route('recovery.settings.update'), [
            'recovery_codes_enabled' => '0',
            'recovery_file_enabled' => '0',
        ])->assertSessionHasErrors('recovery_methods');
        $this->assertFalse($settings->fresh()->recovery_codes_enabled);
        $this->assertTrue($settings->fresh()->recovery_file_enabled);

        $this->put(route('recovery.settings.update'), [
            'recovery_codes_enabled' => '1',
            'recovery_file_enabled' => '1',
            'password_reset_enabled' => '0',
        ])->assertSessionHasErrors('password_reset_enabled');
        $this->assertDatabaseMissing('recovery_settings', ['password_reset_enabled' => 0]);
        $this->assertDatabaseHas('recovery_settings', ['id' => 1, 'recovery_codes_enabled' => 0]);
    }

    public function test_non_admin_cannot_update_recovery_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('recovery.settings.update'), [
                'recovery_codes_enabled' => '1',
                'recovery_file_enabled' => '1',
            ])->assertForbidden();
    }

    public function test_settings_seeder_is_idempotent_and_does_not_overwrite_admin_choices(): void
    {
        RecoverySetting::query()->create([
            'id' => 1,
            'recovery_codes_enabled' => false,
            'recovery_file_enabled' => true,
        ]);

        $this->seed(RecoverySettingsSeeder::class);
        $this->seed(RecoverySettingsSeeder::class);

        $this->assertDatabaseHas('recovery_settings', [
            'id' => 1,
            'recovery_codes_enabled' => 0,
            'recovery_file_enabled' => 1,
        ]);
        $this->assertDatabaseHas('permissions', ['slug' => 'recovery.settings']);
    }

    private function createCode(User $user, string $code): RecoveryCode
    {
        return RecoveryCode::query()->create([
            'user_id' => $user->getKey(),
            'code_hash' => Hash::make($code),
            'generated_at' => now(),
        ]);
    }

    private function createFileCredential(User $user): array
    {
        $payload = [
            'format' => 'sipred-recovery',
            'version' => 1,
            'recovery_id' => (string) \Illuminate\Support\Str::uuid(),
            'credential' => bin2hex(random_bytes(32)),
        ];
        $record = RecoveryFile::query()->create([
            'user_id' => $user->getKey(),
            'recovery_id' => $payload['recovery_id'],
            'credential_hash' => Hash::make($payload['credential']),
            'generated_at' => now(),
        ]);

        return [$payload, $record];
    }

    private function grantRecoverySettings(User $user): void
    {
        $permission = Permission::query()->create([
            'name' => 'Configurar recuperación',
            'slug' => 'recovery.settings',
            'description' => 'Administrar métodos de recuperación.',
        ]);
        $role = Role::query()->create([
            'name' => 'Administrador de prueba',
            'slug' => 'administrador-prueba',
            'description' => 'Rol de prueba.',
            'status' => 'activo',
        ]);
        $role->permissions()->attach($permission->getKey());
        $user->roles()->attach($role->getKey());
    }
}