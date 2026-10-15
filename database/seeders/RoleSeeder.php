<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $administrator = Role::updateOrCreate(
            ['slug' => 'administrador'],
            ['name' => 'Administrador', 'description' => 'Acceso completo a la plataforma.', 'status' => 'activo']
        );
        $operator = Role::updateOrCreate(
            ['slug' => 'operador'],
            ['name' => 'Operador', 'description' => 'Consulta operativa y gestión de alertas.', 'status' => 'activo']
        );
        $viewer = Role::updateOrCreate(
            ['slug' => 'consulta'],
            ['name' => 'Consulta', 'description' => 'Consulta de información sin cambios operativos.', 'status' => 'activo']
        );

        $operatorPermissions = [
            'dashboard.ver', 'zonas.ver', 'sensores.ver', 'mediciones.ver',
            'alertas.ver', 'alertas.gestionar', 'eventos.ver', 'reportes.ver',
        ];
        $viewerPermissions = [
            'dashboard.ver', 'zonas.ver', 'sensores.ver', 'mediciones.ver',
            'alertas.ver', 'eventos.ver',
        ];

        $administrator->permissions()->sync(Permission::query()->pluck('id')->all());
        $operator->permissions()->sync(Permission::query()->whereIn('slug', $operatorPermissions)->pluck('id')->all());
        $viewer->permissions()->sync(Permission::query()->whereIn('slug', $viewerPermissions)->pluck('id')->all());

        $administratorUser = User::query()->where('email', config('sipred.admin_email'))->first();

        if ($administratorUser !== null) {
            $administratorUser->roles()->syncWithoutDetaching([$administrator->getKey()]);
        }
    }
}
