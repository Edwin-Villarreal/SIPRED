<?php

namespace Database\Factories;

use App\Models\Evento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evento>
 */
class EventoFactory extends Factory
{
    protected $model = Evento::class;

    public function definition(): array
    {
        return [
            'usuario_id' => null,
            'sensor_id' => null,
            'zona_id' => null,
            'alerta_id' => null,
            'tipo' => 'sistema',
            'titulo' => 'Evento de prueba',
            'descripcion' => 'Registro de prueba del historial del sistema.',
            'nivel' => 'informativa',
            'origen' => 'sistema',
            'ocurrio_at' => now(),
            'metadata' => [],
        ];
    }
}
