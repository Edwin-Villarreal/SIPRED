<?php

namespace Database\Seeders;

use App\Models\TipoSensor;
use Illuminate\Database\Seeder;

class TipoSensorSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nombre' => 'Temperatura', 'slug' => 'temperatura', 'descripcion' => 'Medición numérica de temperatura.', 'unidad_medida' => '°C'],
            ['nombre' => 'Humedad', 'slug' => 'humedad', 'descripcion' => 'Medición numérica de humedad relativa.', 'unidad_medida' => '%'],
            ['nombre' => 'Nivel de agua', 'slug' => 'nivel-agua', 'descripcion' => 'Medición numérica del nivel de agua.', 'unidad_medida' => 'cm'],
            ['nombre' => 'Humo', 'slug' => 'humo', 'descripcion' => 'Estado de humo codificado numéricamente; el detalle puede guardarse en metadata.', 'unidad_medida' => 'estado'],
        ];

        foreach ($types as $attributes) {
            TipoSensor::firstOrCreate(
                ['slug' => $attributes['slug']],
                $attributes + ['tipo_dato' => 'decimal', 'estado' => 'activo']
            );
        }
    }
}
