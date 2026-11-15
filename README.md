# SIPRED

**Sistema Inteligente de Prevención y Alerta Temprana de Desastres**

SIPRED organiza zonas, sensores y alertas tempranas. La aplicación web y la API IoT usan Laravel y MySQL. Las lecturas DEMO son sintéticas y se etiquetan para separarlas de las reales.

## Arquitectura

~~~text
Web: Laravel / Eloquent → MySQL → Dashboard e historial de mediciones
IoT: Simulador o ESP32 futuro → API REST → token del dispositivo → MySQL
~~~

La API, el simulador y el contrato de datos están descritos en docs/api.md, docs/api-crud.md, docs/simulator.md, docs/architecture.md y docs/iot.md.

La guía para evaluar Performance, Accessibility, Best Practices y SEO con Google Lighthouse en Chrome, guardar informes y corregir hallazgos está en [docs/lighthouse.md](docs/lighthouse.md). No se han atribuido puntuaciones Lighthouse sin ejecutar auditorías.

La SPA React/Vite está en `C:\wamp64\www\mi-frontend` y consume la API Laravel con Axios desde `http://localhost:5173`. Laravel sigue sirviendo las pantallas Blade existentes en el puerto 8000. En local, el CRUD React puede funcionar sin inicio de sesión si se activa `SIPRED_LOCAL_CRUD_WITHOUT_LOGIN=true` en `.env`; fuera de `APP_ENV=local` se requieren sesión y permisos.

## Tecnologías

- Laravel 13, PHP 8.3 o superior y Eloquent.
- MySQL con utf8mb4 e InnoDB.
- Blade, Bootstrap 5 y Vite para las pantallas Laravel.
- React 19, React Router 7, Axios y Vite 8 en la SPA separada `mi-frontend`.
- PHPUnit con SQLite en memoria para las pruebas.

## Configuración local en Windows

1. Instala dependencias solo si faltan: Composer y pnpm/npm.
2. Conserva el .env existente. Configura MySQL en DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME y DB_PASSWORD.
3. En una base de datos existente, verifica el estado antes de ejecutar cambios:

~~~powershell
php artisan migrate:status
~~~

Ejecuta `php artisan migrate` solo si aparecen migraciones pendientes. No uses `migrate:fresh` ni vuelvas a sembrar datos en la base existente.

4. Inicia los dos procesos en terminales separadas:

~~~powershell
php artisan serve --host=127.0.0.1 --port=8000
~~~

~~~powershell
Set-Location C:\wamp64\www\mi-frontend
npm run dev
~~~

Activa `SIPRED_LOCAL_CRUD_WITHOUT_LOGIN=true` en `.env`, reinicia Laravel y abre la SPA en `http://localhost:5173`. Para compilarla o revisar estilo:

~~~powershell
npm run build
npm run lint
~~~

Las pruebas Laravel usan SQLite en memoria:

~~~powershell
Set-Location 'C:\wamp64\www\software SIPRED'
php artisan test
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
- GET/POST/PUT/PATCH/DELETE /api/gestion/zonas y /api/gestion/sensores: CRUD administrativo con sesión, permisos y borrado lógico.
- GET /api/gestion/tipos-sensores: catálogo para los formularios de sensores.
- GET/PATCH /api/gestion/alertas: consulta y transición de estado con registro en historial.
- GET /api/gestion/mediciones y /api/gestion/eventos: consulta de datos históricos.

Las pantallas React permiten gestionar sensores y zonas, y consultar alertas, mediciones y eventos. La administración de usuarios y la integración de hardware físico, firmware, MQTT, SMS, WhatsApp y despliegue público siguen pendientes.
