# Simulador de sensores

El simulador no requiere ESP32. Genera lecturas según el tipo de sensor y las envía por HTTP a los mismos endpoints autenticados que utilizará un dispositivo futuro. No escribe mediciones directamente en MySQL.

## Preparar el servidor local

En una terminal, inicia la aplicación:

~~~powershell
php artisan serve --host=127.0.0.1 --port=8000
~~~

En otra terminal, ejecuta el simulador. La URL predeterminada es http://127.0.0.1:8000; puedes cambiarla con SIPRED_IOT_API_URL.

## Comandos

~~~powershell
php artisan sipred:simulate-sensor SEN-001
php artisan sipred:simulate-sensor SEN-001 --count=10
php artisan sipred:simulate-sensor SEN-003 --value=85
php artisan sipred:simulate-sensor SEN-002 --count=5 --interval=2
~~~

- --count: lecturas por ejecución, de 1 a 100 (predeterminado: 1).
- --interval: segundos entre lecturas, de 0 a 60 (predeterminado: 0).
- --value: valor numérico fijo opcional; si se omite, usa el rango del tipo.

Rangos predeterminados: temperatura 25–40 °C; humedad 40–95 %; nivel de agua 0–100 cm; humo 0 o 1.

El comando solo admite sensores activos marcados como DEMO. Genera un measurement_id nuevo en cada envío, envía también un heartbeat al finalizar y muestra el resultado de cada request. Crea un token aleatorio temporal, guarda su hash y lo revoca al terminar; no imprime ni registra el secreto.

## Verificar el resultado

1. Abre http://127.0.0.1:8000 e inicia sesión.
2. Revisa el contador DEMO, la serie de temperatura y las últimas lecturas del dashboard.
3. Abre Mediciones para filtrar por sensor, tipo, zona, origen, calidad y fechas; entra al identificador para ver el detalle.
4. Para consultar estado autenticado, usa GET /api/iot/sensors/SEN-001/status con el Bearer del sensor.
5. Ejecuta php artisan test para las pruebas API y simulador.

Las lecturas DEMO permanecen identificadas como sintéticas y no se contabilizan como mediciones reales.