<?php

namespace Database\Seeders;

use App\Models\Alerta;
use App\Models\Evento;
use App\Models\Medicion;
use App\Models\Notificacion;
use App\Models\Sensor;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $measurementRows = [
            ['sensor' => 'SEN-001', 'id' => 'DEMO-SEN001-000001', 'value' => 28.5, 'unit' => '°C', 'minutes' => 18, 'metadata' => ['demo' => true, 'sample' => 1]],
            ['sensor' => 'SEN-001', 'id' => 'DEMO-SEN001-000002', 'value' => 32.0, 'unit' => '°C', 'minutes' => 8, 'metadata' => ['demo' => true, 'sample' => 2]],
            ['sensor' => 'SEN-002', 'id' => 'DEMO-SEN002-000001', 'value' => 73.2, 'unit' => '%', 'minutes' => 17, 'metadata' => ['demo' => true, 'sample' => 1]],
            ['sensor' => 'SEN-002', 'id' => 'DEMO-SEN002-000002', 'value' => 82.0, 'unit' => '%', 'minutes' => 7, 'metadata' => ['demo' => true, 'sample' => 2]],
            ['sensor' => 'SEN-003', 'id' => 'DEMO-SEN003-000001', 'value' => 68.4, 'unit' => 'cm', 'minutes' => 16, 'metadata' => ['demo' => true, 'sample' => 1]],
            ['sensor' => 'SEN-003', 'id' => 'DEMO-SEN003-000002', 'value' => 88.0, 'unit' => 'cm', 'minutes' => 6, 'metadata' => ['demo' => true, 'sample' => 2]],
            ['sensor' => 'SEN-004', 'id' => 'DEMO-SEN004-000001', 'value' => 0, 'unit' => 'estado', 'minutes' => 15, 'metadata' => ['demo' => true, 'estado' => 'sin_humo']],
            ['sensor' => 'SEN-004', 'id' => 'DEMO-SEN004-000002', 'value' => 0, 'unit' => 'estado', 'minutes' => 5, 'metadata' => ['demo' => true, 'estado' => 'sin_humo']],
        ];

        foreach ($measurementRows as $row) {
            $sensor = Sensor::query()->where('codigo', $row['sensor'])->firstOrFail();
            $measuredAt = $now->copy()->subMinutes($row['minutes']);

            Medicion::firstOrCreate(
                ['measurement_id' => $row['id']],
                [
                    'sensor_id' => $sensor->getKey(),
                    'valor' => $row['value'],
                    'unidad' => $row['unit'],
                    'calidad_dato' => 'good',
                    'origen' => Medicion::ORIGIN_SIMULATOR,
                    'medida_at' => $measuredAt,
                    'received_at' => $measuredAt,
                    'metadata' => $row['metadata'],
                ]
            );
        }

        foreach (Sensor::query()->whereIn('codigo', ['SEN-001', 'SEN-002', 'SEN-003', 'SEN-004'])->get() as $sensor) {
            $sensor->forceFill([
                'ultima_medicion_at' => $sensor->mediciones()->max('medida_at'),
            ])->save();
        }

        $administrator = User::query()->where('email', config('sipred.admin_email'))->first();
        $alerts = [
            [
                'sensor' => 'SEN-001', 'type' => 'temperatura', 'level' => 'baja',
                'state' => Alerta::STATUS_ACTIVE, 'value' => 35.0, 'threshold' => 32.0,
                'minutes' => 12, 'title' => 'Temperatura por encima del umbral de prueba',
                'message' => 'Escenario DEMO; esta condicion no corresponde a una lectura real.',
            ],
            [
                'sensor' => 'SEN-002', 'type' => 'humedad', 'level' => 'media',
                'state' => Alerta::STATUS_ACKNOWLEDGED, 'value' => 86.0, 'threshold' => 80.0,
                'minutes' => 38, 'title' => 'Humedad elevada de prueba',
                'message' => 'Escenario DEMO reconocido para comprobar el flujo de alertas.',
            ],
            [
                'sensor' => 'SEN-004', 'type' => 'humo', 'level' => 'alta',
                'state' => Alerta::STATUS_RESOLVED, 'value' => 1.0, 'threshold' => 0.0,
                'minutes' => 75, 'title' => 'Estado de humo de prueba',
                'message' => 'Escenario DEMO resuelto; no se detecto humo en un sitio real.',
            ],
            [
                'sensor' => 'SEN-003', 'type' => 'nivel_agua', 'level' => 'critica',
                'state' => Alerta::STATUS_ACTIVE, 'value' => 88.0, 'threshold' => 80.0,
                'minutes' => 4, 'title' => 'Nivel de agua critico de prueba',
                'message' => 'Escenario DEMO; no representa una emergencia ni una lectura real.',
            ],
        ];

        foreach ($alerts as $row) {
            $sensor = Sensor::query()->where('codigo', $row['sensor'])->firstOrFail();
            $startedAt = $now->copy()->subMinutes($row['minutes']);
            $resolved = $row['state'] === Alerta::STATUS_RESOLVED;
            $acknowledged = in_array($row['state'], [Alerta::STATUS_ACKNOWLEDGED, Alerta::STATUS_RESOLVED], true);

            $alert = Alerta::firstOrCreate(
                [
                    'sensor_id' => $sensor->getKey(),
                    'tipo_alerta' => $row['type'],
                    'nivel' => $row['level'],
                    'titulo' => 'DEMO · '.$row['title'],
                ],
                [
                    'zona_id' => $sensor->zona_id,
                    'mensaje' => $row['message'],
                    'valor_detectado' => $row['value'],
                    'umbral' => $row['threshold'],
                    'unidad' => $sensor->unidad_medida,
                    'estado' => $row['state'],
                    'origen' => Alerta::ORIGIN_SIMULATOR,
                    'fecha_hora_inicio' => $startedAt,
                    'fecha_hora_fin' => $resolved ? $now->copy()->subMinutes(20) : null,
                    'reconocida_por' => $acknowledged ? $administrator?->getKey() : null,
                    'reconocida_at' => $acknowledged ? $startedAt->copy()->addMinutes(2) : null,
                    'resuelta_por' => $resolved ? $administrator?->getKey() : null,
                    'resuelta_at' => $resolved ? $now->copy()->subMinutes(20) : null,
                ]
            );

            Evento::firstOrCreate(
                ['alerta_id' => $alert->getKey(), 'tipo' => 'alerta', 'titulo' => 'DEMO · '.$row['title']],
                [
                    'usuario_id' => $administrator?->getKey(),
                    'sensor_id' => $sensor->getKey(),
                    'zona_id' => $sensor->zona_id,
                    'descripcion' => $row['message'],
                    'nivel' => $row['level'],
                    'origen' => 'simulador',
                    'ocurrio_at' => $startedAt,
                    'metadata' => ['demo' => true],
                ]
            );

            if ($row['level'] === 'critica' && $administrator !== null) {
                Notificacion::firstOrCreate(
                    [
                        'usuario_id' => $administrator->getKey(),
                        'alerta_id' => $alert->getKey(),
                        'tipo' => 'alerta_demo',
                    ],
                    [
                        'titulo' => 'DEMO · '.$row['title'],
                        'mensaje' => 'Notificacion local de demostracion; no se envio a canales externos.',
                        'leida_at' => null,
                        'enviada_at' => null,
                        'estado' => 'pendiente',
                        'metadata' => ['demo' => true, 'external_delivery' => false],
                    ]
                );
            }
        }
    }
}
