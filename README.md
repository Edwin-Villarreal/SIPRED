# SIPRED

**Sistema Inteligente de Prevención y Alerta Temprana de Desastres**

SIPRED organiza zonas, sensores y alertas tempranas. La aplicación web y la API IoT usan Laravel y MySQL. Las lecturas DEMO son sintéticas y se etiquetan para separarlas de las reales.

## Arquitectura

~~~text
Web: Laravel / Eloquent → MySQL → Dashboard e historial de mediciones
IoT: Simulador o ESP32 futuro → API REST → token del dispositivo → MySQL
~~~

La API, el simulador y el contrato de datos están descritos en docs/api.md, docs/simulator.md, docs/architecture.md y docs/iot.md.

## Tecnologías

- Laravel 13, PHP 8.3 o superior y Eloquent.
- MySQL con utf8mb4 e InnoDB.
- Blade, Bootstrap 5, JavaScript y Vite.
- PHPUnit con SQLite en memoria para las pruebas.

## Configuración local en Windows

1. Instala dependencias solo si faltan: Composer y pnpm/npm.
2. Conserva el .env existente. Configura MySQL en DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME y DB_PASSWORD.
3. Aplica migraciones incrementales y datos iniciales:

~~~powershell
php artisan migrate
php artisan db:seed
~~~

4. Construye el frontend y prueba:

~~~powershell
pnpm build
php artisan test
~~~

5. Inicia el servidor local:

~~~powershell
php artisan serve --host=127.0.0.1 --port=8000
~~~

## Simular un sensor

Con el servidor en ejecución, abre otra terminal en la carpeta del proyecto:

~~~powershell
php artisan sipred:simulate-sensor SEN-001
php artisan sipred:simulate-sensor SEN-003 --value=85
php artisan sipred:simulate-sensor SEN-002 --count=10 --interval=2
~~~

El simulador usa la misma API IoT que un ESP32 futuro. Solo trabaja con sensores activos DEMO; genera IDs nuevos, envía mediciones y un heartbeat, y revoca el token temporal al finalizar.

Para emitir una credencial real de dispositivo, usa:

~~~powershell
php artisan sipred:issue-device-token SEN-001 --name="ESP32 SEN-001"
~~~

El token se muestra una sola vez y se almacena solo como hash. No lo compartas ni lo agregues a Git.

## Identidad visual

El logo SIPRED está en public/assets/branding/sipred.jpeg y se utiliza en login y sidebar. La paleta permanece en resources/css/sipred.css.

## Rutas

- GET/POST /login: inicio de sesión web.
- POST /logout: cierre de sesión.
- GET /dashboard: indicadores, lecturas recientes y serie de temperatura.
- GET /mapa-riesgos: zonas activas, riesgo, sensores, alertas y coordenadas registradas.
- GET /sensores: inventario y búsqueda de sensores; acceso a sus mediciones.
- GET /mediciones y GET /mediciones/{id}: consulta filtrada y detalle.
- GET /alertas: alertas registradas y su estado.
- GET /eventos: cronología de eventos.
- GET /reportes: resumen de mediciones y actividad de los últimos siete días.
- GET /usuarios: consulta de cuentas y roles.
- GET /configuracion: parámetros activos sin exponer secretos.
- POST /api/iot/measurements: ingesta idempotente.
- POST /api/iot/heartbeat: conexión y telemetría de dispositivo.
- GET /api/iot/sensors/{sensor_code}/status: estado protegido del sensor.

Las pantallas actuales permiten consultar estos módulos; las operaciones CRUD de zonas, sensores y usuarios, junto con hardware físico, firmware, MQTT, SMS, WhatsApp y despliegue público siguen pendientes.