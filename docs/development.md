# Desarrollo local

## Primer inicio

1. Instala dependencias PHP con `composer install`.
2. Configura `.env`: clave de aplicación, MySQL y `SIPRED_ADMIN_PASSWORD`.
3. Crea la base `sipred`, ejecuta `php artisan migrate --seed`.
4. Instala dependencias JS con `npm install` y genera recursos con `npm run build`.
5. Ejecuta `php artisan serve --host=127.0.0.1 --port=8000`.

Abre `http://127.0.0.1:8000`. Para desarrollar estilos en vivo se puede ejecutar `npm run dev` en otra terminal.

## Verificaciones

```powershell
php artisan --version
php artisan route:list
php artisan migrate:status
php artisan test
npm run build
```

Las pruebas corren sobre SQLite en memoria. Para validar la conexión de MySQL, usa `php artisan migrate:status` después de configurar `.env`.

## Alcance actual

La pantalla de inicio solicita sesión. El usuario inicial se crea con el seeder y la contraseña configurada en `.env`. El dashboard presenta estados vacíos explícitos porque todavía no hay tablas ni hardware de monitoreo.

## Identidad y dependencias

Mantén los colores oficiales en variables CSS. El logo debe ser el archivo oficial proporcionado por el usuario. Agrega paquetes solo cuando la siguiente funcionalidad los requiera; Chart.js y Leaflet pertenecen a sus fases de gráficos y mapa.
