<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('sipred.admin_email');
        $password = config('sipred.admin_password');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException(
                'Define SIPRED_ADMIN_PASSWORD en .env antes de crear el usuario inicial.'
            );
        }

        $administrator = User::withTrashed()->firstOrNew(['email' => $email]);
        $administrator->fill([
            'name' => 'Administracion SIPRED',
            'password' => $password,
            'status' => User::STATUS_ACTIVE,
        ]);

        if ($administrator->exists && $administrator->trashed()) {
            $administrator->restore();
        }

        $administrator->save();

        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            RecoverySettingsSeeder::class,
            TipoSensorSeeder::class,
            ZonaSeeder::class,
            SensorSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
