# Integración IoT SIPRED

## Flujo implementado

~~~text
Sensor/simulador
  → HTTP + JSON
  → Bearer token específico del sensor
  → Form Request
  → MeasurementIngestionService / HeartbeatService
  → transacción MySQL
  → mediciones, sensor_heartbeats, eventos
  → dashboard e historial /mediciones
~~~

El simulador usa estos mismos endpoints y no inserta mediciones directamente. Las lecturas DEMO y ESP32 se distinguen por su origen; el servidor decide el origen a partir de la marca de demostración del sensor.

## Dispositivos físicos

No existe ESP32 físico ni se ha seleccionado hardware. La API ya acepta la forma de datos prevista y admite reintentos con measurement_id único. El almacenamiento offline, firmware, MQTT, cableado y sensores reales quedan para una fase posterior.

Consulta docs/api.md para contratos HTTP y docs/simulator.md para ejecutar el simulador.