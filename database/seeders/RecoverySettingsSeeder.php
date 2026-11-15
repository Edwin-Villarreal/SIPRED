<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\RecoverySetting;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RecoverySettingsSeeder extends Seeder
{
    public function run(): void
    {
        RecoverySetting::query()->firstOrCreate(
            ['id' => 1],
            ['recovery_codes_enabled' => true, 'recovery_file_enabled' => true],
        );

        $permission = Permission::query()->updateOrCreate(
            ['slug' => 'recovery.settings'],
            [
                'name' => 'Configurar recuperación de cuentas',
                'description' => 'Habilitar o deshabilitar métodos de recuperación de cuentas.',
            ],
        );

        $administrator = Role::query()->where('slug', 'administrador')->first();
        $administrator?->permissions()->syncWithoutDetaching([$permission->getKey()]);
    }
}