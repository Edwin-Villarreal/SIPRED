# Firmware ESP32 (fase futura)

Esta carpeta reserva el espacio para el firmware C/C++ que se desarrollará cuando se elijan el dispositivo, los sensores y el entorno de compilación.

La implementación futura cubrirá conexión Wi-Fi, validación de lecturas, identificadores únicos, cola local, reintentos con espera progresiva, reenvío de pendientes y heartbeat. El firmware usará credenciales de dispositivo independientes de las cuentas web.

No se incluyen pines, voltajes, cableado ni modelos de sensor porque el hardware aún no se ha seleccionado. La cola podrá cubrir pérdida de Internet; para pérdida de energía se necesitará alimentación de respaldo.
