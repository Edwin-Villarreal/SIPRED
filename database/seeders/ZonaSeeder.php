<?php

namespace Database\Seeders;

use App\Models\Zona;
use Illuminate\Database\Seeder;

class ZonaSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            ['nombre' => 'El Espinal Centro', 'slug' => 'el-espinal-centro', 'nivel_riesgo' => 'medio'],
            ['nombre' => 'El Espinal Norte', 'slug' => 'el-espinal-norte', 'nivel_riesgo' => 'alto'],
            ['nombre' => 'El Espinal Sur', 'slug' => 'el-espinal-sur', 'nivel_riesgo' => 'critico'],
        ];

        foreach ($zones as $attributes) {
            Zona::firstOrCreate(
                ['slug' => $attributes['slug']],
                $attributes + [
                    'descripcion' => 'Zona de demostracion sin coordenadas reales; registro para pruebas de SIPRED.',
                    'municipio' => 'El Espinal',
                    'departamento' => 'Tolima',
                    'pais' => 'Colombia',
                    'latitud' => null,
                    'longitud' => null,
                    'estado' => 'activa',
                    'is_demo' => true,
                ]
            );
        }
    }
}
