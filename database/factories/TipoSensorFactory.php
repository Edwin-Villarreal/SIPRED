<?php

namespace Database\Factories;

use App\Models\TipoSensor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TipoSensor>
 */
class TipoSensorFactory extends Factory
{
    protected $model = TipoSensor::class;

    public function definition(): array
    {
        $nombre = fake()->unique()->word();

        return [
            'nombre' => ucfirst($nombre),
            'slug' => Str::slug($nombre),
            'descripcion' => fake()->sentence(),
            'unidad_medida' => 'unidad',
            'tipo_dato' => 'decimal',
            'estado' => 'activo',
        ];
    }
}
