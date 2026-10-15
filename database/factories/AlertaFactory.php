<?php

namespace Database\Factories;

use App\Models\Alerta;
use App\Models\Sensor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alerta>
 */
class AlertaFactory extends Factory
{
    protected $model = Alerta::class;

    public function definition(): array
    {
        return [
            'sensor_id' => Sensor::factory(),
            'zona_id' => null,
            'tipo_alerta' => 'sensor',
            'nivel' => Alerta::LEVEL_LOW,
            'titulo' => 'Alerta de prueba',
            'mensaje' => 'Registro de prueba de la alerta.',
            'valor_detectado' => 10,
            'umbral' => 8,
            'unidad' => 'unidad',
            'estado' => Alerta::STATUS_ACTIVE,
            'origen' => Alerta::ORIGIN_SIMULATOR,
            'fecha_hora_inicio' => now(),
        ];
    }

    public function demo(): static
    {
        return $this->state(fn (): array => [
            'origen' => Alerta::ORIGIN_SIMULATOR,
            'titulo' => 'DEMO · Alerta simulada',
            'mensaje' => 'Dato de demostracion; no corresponde a una alerta real.',
        ]);
    }
}
