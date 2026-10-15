<?php

namespace App\Console\Commands;

use App\Models\Sensor;
use App\Models\SensorToken;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

class IssueDeviceTokenCommand extends Command
{
    protected $signature = 'sipred:issue-device-token {sensor_code} {--name=Dispositivo SIPRED} {--expires=}';

    protected $description = 'Genera una credencial de dispositivo y guarda únicamente su hash.';

    public function handle(): int
    {
        $sensorCode = trim((string) $this->argument('sensor_code'));
        $sensor = Sensor::query()->where('codigo', $sensorCode)->first();

        if ($sensor === null) {
            $this->error('No existe el sensor indicado.');

            return self::FAILURE;
        }

        if ($sensor->estado !== Sensor::STATUS_ACTIVE) {
            $this->error('El sensor no está activo.');

            return self::FAILURE;
        }

        $name = trim((string) $this->option('name'));

        if ($name === '' || mb_strlen($name) > 120) {
            $this->error('El nombre del token debe tener entre 1 y 120 caracteres.');

            return self::FAILURE;
        }

        $expiresAt = null;

        if ($this->option('expires') !== null) {
            try {
                $expiresAt = Carbon::parse((string) $this->option('expires'));
            } catch (Throwable) {
                $this->error('La expiración debe ser una fecha válida.');

                return self::FAILURE;
            }

            if ($expiresAt->isPast()) {
                $this->error('La fecha de expiración debe estar en el futuro.');

                return self::FAILURE;
            }
        }

        $plainTextToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        SensorToken::query()->create([
            'sensor_id' => $sensor->getKey(),
            'token_hash' => SensorToken::hashPlainTextToken($plainTextToken),
            'nombre' => $name,
            'expira_at' => $expiresAt,
            'estado' => SensorToken::STATUS_ACTIVE,
        ]);

        $this->newLine();
        $this->warn('Copia el token ahora. Solo se muestra una vez; SIPRED guarda únicamente su hash.');
        $this->line($plainTextToken);
        $this->newLine();
        $this->line('Identidad vinculada: '.$sensor->codigo.' · '.$name);

        return self::SUCCESS;
    }
}