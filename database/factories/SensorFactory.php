<?php

namespace Database\Factories;

use App\Models\Sensor;
use App\Models\TipoSensor;
use App\Models\Zona;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sensor>
 */
class SensorFactory extends Factory
{
    protected $model = Sensor::class;

    public function definition(): array
    {
        return [
            'tipo_sensor_id' => TipoSensor::factory(),
            'zona_id' => Zona::factory(),
            'codigo' => 'SEN-'.Str::upper(Str::random(8)),
            'nombre' => fake()->words(2, true),
            'descripcion' => fake()->sentence(),
            'unidad_medida' => 'unidad',
            'modelo' => null,
            'fabricante' => null,
            'estado' => Sensor::STATUS_ACTIVE,
            'intervalo_lectura' => 60,
            'umbral_minimo' => null,
            'umbral_maximo' => null,
            'fecha_instalacion' => null,
            'ultima_medicion_at' => null,
            'ultima_conexion_at' => null,
            'is_demo' => false,
        ];
    }

    public function demo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_demo' => true,
            'descripcion' => 'Sensor simulado para pruebas; no corresponde a hardware instalado.',
        ]);
    }
}
