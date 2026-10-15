<?php

namespace Database\Seeders;

use App\Models\Sensor;
use App\Models\TipoSensor;
use App\Models\Zona;
use Illuminate\Database\Seeder;

class SensorSeeder extends Seeder
{
    public function run(): void
    {
        $sensors = [
            [
                'codigo' => 'SEN-001', 'nombre' => 'Temperatura de demostracion',
                'tipo' => 'temperatura', 'zona' => 'el-espinal-centro',
                'unit' => '°C', 'minimum' => 5, 'maximum' => 45,
            ],
            [
                'codigo' => 'SEN-002', 'nombre' => 'Humedad de demostracion',
                'tipo' => 'humedad', 'zona' => 'el-espinal-norte',
                'unit' => '%', 'minimum' => 10, 'maximum' => 90,
            ],
            [
                'codigo' => 'SEN-003', 'nombre' => 'Nivel de agua de demostracion',
                'tipo' => 'nivel-agua', 'zona' => 'el-espinal-sur',
                'unit' => 'cm', 'minimum' => 0, 'maximum' => 100,
            ],
            [
                'codigo' => 'SEN-004', 'nombre' => 'Humo de demostracion',
                'tipo' => 'humo', 'zona' => 'el-espinal-centro',
                'unit' => 'estado', 'minimum' => 0, 'maximum' => 1,
            ],
        ];

        foreach ($sensors as $attributes) {
            $type = TipoSensor::query()->where('slug', $attributes['tipo'])->firstOrFail();
            $zone = Zona::query()->where('slug', $attributes['zona'])->firstOrFail();

            Sensor::firstOrCreate(
                ['codigo' => $attributes['codigo']],
                [
                    'tipo_sensor_id' => $type->getKey(),
                    'zona_id' => $zone->getKey(),
                    'nombre' => $attributes['nombre'],
                    'descripcion' => 'Sensor simulado para pruebas; no corresponde a hardware instalado.',
                    'unidad_medida' => $attributes['unit'],
                    'modelo' => null,
                    'fabricante' => null,
                    'estado' => Sensor::STATUS_ACTIVE,
                    'intervalo_lectura' => 60,
                    'umbral_minimo' => $attributes['minimum'],
                    'umbral_maximo' => $attributes['maximum'],
                    'fecha_instalacion' => null,
                    'ultima_medicion_at' => null,
                    'ultima_conexion_at' => null,
                    'is_demo' => true,
                ]
            );
        }
    }
}
