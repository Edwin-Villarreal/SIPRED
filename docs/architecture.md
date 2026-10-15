# Arquitectura SIPRED

## Flujo actual

~~~text
Usuarios web → Laravel / Eloquent → MySQL → Dashboard e historial de mediciones
Simulador o ESP32 futuro → API REST → token de dispositivo → validación
    → servicios transaccionales → mediciones / heartbeats / eventos
~~~

La autenticación IoT utiliza tokens por sensor, separados de las sesiones web. En la base se conserva solo el hash SHA-256 del token. Las lecturas usan measurement_id único para tolerar reenvíos después de una pérdida de conexión.

## Procesamiento de mediciones

MeasurementIngestionService verifica el sensor y su estado, evita duplicados, guarda la lectura, actualiza última medición/conexión y registra el evento en una transacción. Si llega un duplicado del mismo sensor, devuelve duplicate sin insertar otra fila. Heartbeats pasan por HeartbeatService, conservan el historial y generan eventos cuando cambia el estado reportado.

El dashboard prioriza la serie de temperatura real si existe; de lo contrario presenta una serie DEMO identificada. El historial /mediciones ofrece filtros, orden y paginación. No mezcla silenciosamente fuentes.

## Estado

- **Fases 1–2:** aplicación web, autenticación, esquema incremental, modelos y datos DEMO.
- **Fase 3:** los modelos de zonas/sensores existen, pero el CRUD y las pantallas de gestión todavía no están implementados.
- **Fase 4:** API IoT, tokens de dispositivo, mediciones, heartbeat, estado, simulador e historial de mediciones.
- **Pendiente:** CRUD de zonas/sensores, mapa de riesgos, reglas automáticas de alertas, firmware y hardware físico.

No se han definido pines, sensores, voltajes, firmware definitivo, MQTT, SMS, WhatsApp ni despliegue público.