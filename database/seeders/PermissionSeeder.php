<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'Ver dashboard', 'slug' => 'dashboard.ver', 'description' => 'Consultar el panel de situación.'],
            ['name' => 'Ver usuarios', 'slug' => 'usuarios.ver', 'description' => 'Consultar usuarios.'],
            ['name' => 'Crear usuarios', 'slug' => 'usuarios.crear', 'description' => 'Registrar usuarios.'],
            ['name' => 'Editar usuarios', 'slug' => 'usuarios.editar', 'description' => 'Modificar usuarios.'],
            ['name' => 'Eliminar usuarios', 'slug' => 'usuarios.eliminar', 'description' => 'Eliminar usuarios.'],
            ['name' => 'Ver roles', 'slug' => 'roles.ver', 'description' => 'Consultar roles.'],
            ['name' => 'Gestionar roles', 'slug' => 'roles.gestionar', 'description' => 'Asignar roles y permisos.'],
            ['name' => 'Ver zonas', 'slug' => 'zonas.ver', 'description' => 'Consultar zonas.'],
            ['name' => 'Crear zonas', 'slug' => 'zonas.crear', 'description' => 'Registrar zonas.'],
            ['name' => 'Editar zonas', 'slug' => 'zonas.editar', 'description' => 'Modificar zonas.'],
            ['name' => 'Eliminar zonas', 'slug' => 'zonas.eliminar', 'description' => 'Retirar zonas.'],
            ['name' => 'Ver sensores', 'slug' => 'sensores.ver', 'description' => 'Consultar sensores.'],
            ['name' => 'Crear sensores', 'slug' => 'sensores.crear', 'description' => 'Registrar sensores.'],
            ['name' => 'Editar sensores', 'slug' => 'sensores.editar', 'description' => 'Modificar sensores.'],
            ['name' => 'Eliminar sensores', 'slug' => 'sensores.eliminar', 'description' => 'Retirar sensores.'],
            ['name' => 'Ver mediciones', 'slug' => 'mediciones.ver', 'description' => 'Consultar mediciones.'],
            ['name' => 'Ver alertas', 'slug' => 'alertas.ver', 'description' => 'Consultar alertas.'],
            ['name' => 'Gestionar alertas', 'slug' => 'alertas.gestionar', 'description' => 'Reconocer y resolver alertas.'],
            ['name' => 'Ver eventos', 'slug' => 'eventos.ver', 'description' => 'Consultar el historial de eventos.'],
            ['name' => 'Ver reportes', 'slug' => 'reportes.ver', 'description' => 'Consultar reportes.'],
            ['name' => 'Generar reportes', 'slug' => 'reportes.generar', 'description' => 'Generar reportes.'],
            ['name' => 'Ver configuración', 'slug' => 'configuracion.ver', 'description' => 'Consultar la configuración.'],
            ['name' => 'Editar configuración', 'slug' => 'configuracion.editar', 'description' => 'Modificar la configuración.'],
        ];

        foreach ($permissions as $attributes) {
            Permission::updateOrCreate(
                ['slug' => $attributes['slug']],
                ['name' => $attributes['name'], 'description' => $attributes['description']]
            );
        }
    }
}
