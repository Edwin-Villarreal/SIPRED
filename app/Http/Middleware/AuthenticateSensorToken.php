<?php

namespace App\Http\Middleware;

use App\Models\Sensor;
use App\Models\SensorToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSensorToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');
        $routeSensorCode = $request->route('sensor');
        $sensorCode = is_string($routeSensorCode) ? $routeSensorCode : $request->input('sensor_code');

        if (! is_string($sensorCode) || trim($sensorCode) === '') {
            return response()->json([
                'success' => false,
                'message' => 'El código del sensor es obligatorio.',
            ], 422);
        }

        $sensor = Sensor::query()->where('codigo', trim($sensorCode))->first();

        if ($sensor === null) {
            Log::notice('Solicitud IoT para un sensor inexistente.', [
                'sensor_code' => trim($sensorCode),
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sensor no encontrado.',
            ], 404);
        }

        $plainTextToken = $request->bearerToken();
        $token = is_string($plainTextToken) && $plainTextToken !== ''
            ? SensorToken::query()
                ->where('sensor_id', $sensor->getKey())
                ->where('token_hash', SensorToken::hashPlainTextToken($plainTextToken))
                ->first()
            : null;

        if ($token === null || ! $token->matchesPlainTextToken($plainTextToken)) {
            Log::warning('Autenticación IoT rechazada.', [
                'sensor_code' => $sensor->codigo,
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token de dispositivo inválido o vencido.',
            ], 401);
        }

        $token->forceFill(['ultimo_uso_at' => now()])->save();
        $request->attributes->set('iot_sensor', $sensor);
        $request->attributes->set('iot_sensor_token', $token);

        return $next($request);
    }
}