# API IoT SIPRED

## Estado y autenticación

La API recibe datos HTTP/JSON de sensores. Está separada de las sesiones web y no acepta contraseñas de usuarios. Cada token pertenece a un sensor, se guarda únicamente como SHA-256 y puede tener expiración o revocación.

Para emitir una credencial para un dispositivo:

~~~powershell
php artisan sipred:issue-device-token SEN-001 --name="ESP32 SEN-001"
~~~

El token completo se imprime una sola vez en consola. Cópialo al almacén seguro del dispositivo; SIPRED no conserva el texto plano. No lo pegues en tickets, repositorios o logs.

Todas las rutas IoT requieren:

~~~http
Authorization: Bearer TOKEN_DEL_DISPOSITIVO
Accept: application/json
Content-Type: application/json
~~~

El sensor_code del JSON o de la ruta debe coincidir con el sensor al que pertenece el token. El límite actual es de 120 solicitudes por minuto por IP.

## POST /api/iot/measurements

Registra mediciones en una transacción: inserta la lectura, actualiza ultima_medicion_at y ultima_conexion_at, y agrega un evento. El servidor determina el origen (simulador para sensores DEMO, esp32 para sensores normales); el cliente no puede elegirlo.

~~~json
{
  "sensor_code": "SEN-001",
  "measurement_id": "ESP32-SEN001-000001",
  "value": 72.5,
  "unit": "cm",
  "measured_at": "2026-10-08T14:30:00Z",
  "quality": "good"
}
~~~

measurement_id tiene índice único. Reenviar la misma medición del mismo sensor devuelve HTTP 200 con “Medición ya registrada” y no crea otro evento. Reutilizar ese ID para un sensor distinto devuelve HTTP 409.

## POST /api/iot/heartbeat

Guarda una muestra de conexión y actualiza la última conexión del sensor.

~~~json
{
  "sensor_code": "SEN-001",
  "device_id": "ESP32-SEN001",
  "firmware_version": "0.1.0",
  "signal_strength": -67,
  "battery_level": 88,
  "status": "online"
}
~~~

device_id, versión, señal y batería son opcionales. La señal acepta de -120 a 0 dBm; batería de 0 a 100. Los valores status admitidos son online, offline, warning y unknown. Se conserva cada heartbeat y solo se crea evento de conexión cuando el estado cambia o es el primero.

## GET /api/iot/sensors/{sensor_code}/status

Devuelve estado administrativo, conectividad, última actividad y último valor, sin revelar tokens:

~~~json
{
  "success": true,
  "data": {
    "sensor_code": "SEN-001",
    "status": "activo",
    "connectivity": "online",
    "last_seen_at": "2026-10-08T14:30:00Z",
    "last_measurement_at": "2026-10-08T14:30:00Z",
    "last_measurement": {
      "value": 72.5,
      "unit": "cm",
      "origin": "esp32"
    },
    "last_heartbeat_at": "2026-10-08T14:30:00Z"
  }
}
~~~

La conectividad se considera offline si no se recibió actividad durante SIPRED_IOT_CONNECTIVITY_TIMEOUT segundos (300 por defecto) o el último heartbeat reportó desconexión. Ese cálculo no modifica el estado administrativo del sensor.

## Respuestas y errores

| HTTP | Comportamiento |
| --- | --- |
| 201 | Medición o heartbeat aceptado. |
| 200 | Medición repetida ya registrada, sin duplicado. |
| 401 | Token ausente, inválido, revocado o vencido. |
| 404 | Código de sensor inexistente. |
| 409 | Sensor inactivo o measurement_id asociado a otro sensor. |
| 422 | JSON/campos inválidos. |
| 429 | Límite de solicitudes excedido. |
| 500 | Error interno con mensaje genérico; no devuelve detalles de la base de datos. |

Los logs registran sensor, identificador y tipo de evento cuando corresponde; nunca guardan el token Bearer ni contraseñas.