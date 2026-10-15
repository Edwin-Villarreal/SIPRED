<?php

namespace Database\Factories;

use App\Models\Zona;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Zona>
 */
class ZonaFactory extends Factory
{
    protected $model = Zona::class;

    public function definition(): array
    {
        $nombre = fake()->unique()->city();

        return [
            'nombre' => $nombre,
            'slug' => Str::slug($nombre),
            'descripcion' => fake()->sentence(),
            'municipio' => 'El Espinal',
            'departamento' => 'Tolima',
            'pais' => 'Colombia',
            'latitud' => null,
            'longitud' => null,
            'nivel_riesgo' => 'bajo',
            'estado' => 'activa',
            'is_demo' => false,
        ];
    }

    public function demo(): static
    {
        return $this->state(fn (): array => [
            'is_demo' => true,
            'descripcion' => 'Dato de demostracion; no representa una zona real configurada.',
            'latitud' => null,
            'longitud' => null,
        ]);
    }
}
