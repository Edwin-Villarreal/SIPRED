<?php

namespace Database\Factories;

use App\Models\Medicion;
use App\Models\Sensor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Medicion>
 */
class MedicionFactory extends Factory
{
    protected $model = Medicion::class;

    public function definition(): array
    {
        return [
            'sensor_id' => Sensor::factory(),
            'measurement_id' => 'DEMO-'.Str::upper(Str::random(18)),
            'valor' => fake()->randomFloat(2, 0, 100),
            'unidad' => 'unidad',
            'calidad_dato' => 'good',
            'origen' => Medicion::ORIGIN_SIMULATOR,
            'medida_at' => now(),
            'received_at' => now(),
            'metadata' => ['demo' => true],
        ];
    }
}
