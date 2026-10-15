# Base de datos SIPRED

## Conexión

La aplicación utiliza MySQL con utf8mb4 e InnoDB. La conexión se configura en .env; no se debe compartir ni subir. PHPUnit usa SQLite en memoria según phpunit.xml.

## Tablas y relaciones

| Tabla | Propósito |
| --- | --- |
| users, roles, permissions, role_user, permission_role | Usuarios web y asignación de roles/permisos. |
| zonas, tipos_sensores, sensores | Organización territorial, catálogo y dispositivos lógicos. Zonas y sensores conservan baja lógica y marca DEMO. |
| mediciones | Lecturas numéricas, id único, valor, unidad, calidad, origen, fecha de medida/recepción y metadata JSON. |
| alertas, eventos, notificaciones | Riesgos, historial y avisos internos. |
| sensor_tokens | Hash de credenciales de dispositivo, nunca texto plano. |
| sensor_heartbeats | Señal, batería, firmware, estado e IP reportados. |
| audit_logs | Estructura para auditoría de cambios. |

Las tablas Laravel de cache, sesiones y trabajos se conservan.

~~~text
users ──< role_user >── roles ──< permission_role >── permissions
zonas ──< sensores >── tipos_sensores
sensores ──< mediciones
sensores ──< alertas / eventos / sensor_tokens / sensor_heartbeats
alertas ──< notificaciones
users ──< eventos / notificaciones / audit_logs
~~~

Las claves foráneas restringen eliminaciones que afectarían historial; las referencias opcionales se ponen en NULL. Los pivotes tienen índices únicos compuestos. measurement_id es único para idempotencia.

## Flujo de API

sensor_code → sensores.codigo → sensor_id → mediciones

MeasurementIngestionService ejecuta en una transacción la creación de la medición, actualización de última medición/conexión y creación del evento. Un reenvío del mismo measurement_id y sensor devuelve duplicate; un ID asociado a otro sensor se rechaza. HeartbeatService conserva cada muestra y registra evento cuando cambia el estado.

La API incluye POST /api/iot/measurements, POST /api/iot/heartbeat y GET /api/iot/sensors/{sensor}/status. Los tokens se vinculan a un sensor y solo se guarda SHA-256. Consulta docs/api.md.

## Datos iniciales y DEMO

El seeder crea roles, permisos, tipos, tres zonas y cuatro sensores demo, más un pequeño conjunto de mediciones, alertas, eventos y notificación. Los datos sintéticos usan is_demo u origen simulador; no representan sensores instalados, emergencias reales ni coordenadas verificadas.

El simulador genera un token temporal, persiste solo su hash y lo revoca después de llamar a la API. El comando para dispositivos reales muestra el token generado una sola vez; la base guarda únicamente el hash.

## Migraciones y verificación

Las migraciones de Fase 2 son incrementales; Fase 4 no requiere recrear tablas.

~~~powershell
php artisan migrate:status
php artisan migrate
php artisan db:seed
php artisan test
php artisan sipred:simulate-sensor SEN-001
~~~

No uses migrate:fresh ni migrate:refresh en una base con datos.