# Pruebas manuales de la API administrativa SIPRED

Estas rutas sirven a las pantallas SIPRED y a clientes de prueba. En desarrollo local se puede habilitar el CRUD sin cuenta con `SIPRED_LOCAL_CRUD_WITHOUT_LOGIN=true` en `.env`, siempre que `APP_ENV=local`. En otros entornos requieren una sesión web iniciada y los permisos de la operación. Las escrituras conservan la validación y protección CSRF.

## Preparar Thunder Client

1. Inicia Laravel en `http://localhost:8000`.
2. Usa `Accept: application/json` y, al enviar cuerpos JSON, `Content-Type: application/json`.
3. En modo local sin sesión, las solicitudes CRUD funcionan desde esta computadora. Para escrituras manuales desde Thunder Client, envía `GET /api/csrf-cookie` y usa el valor `csrf_token` como `X-CSRF-TOKEN`; Laravel conserva la cookie CSRF.
4. En otros entornos, inicia sesión en `/login` y conserva la cookie `laravel_session`. Después vuelve a pedir `GET /api/csrf-cookie` y usa su token para las escrituras. Thunder Client puede guardar las cookies; si no, agrégalas en el encabezado `Cookie`.

La SPA React/Vite está separada en `C:\wamp64\www\mi-frontend` (origen `http://localhost:5173`) y llama con Axios a `http://localhost:8000/api`. Laravel permite ese origen con credenciales CORS. En local, la interfaz funciona sin iniciar sesión si está activada la opción del `.env`; en los demás entornos se usan la cookie de sesión y el token CSRF. Las rutas de dispositivos IoT siguen usando un token Bearer independiente.

## Zonas

| Operación | Solicitud |
| --- | --- |
| Listar | `GET /api/gestion/zonas` |
| Consultar una zona | `GET /api/gestion/zonas/{id}` |
| Crear | `POST /api/gestion/zonas` |
| Actualizar completa o parcialmente | `PUT` o `PATCH /api/gestion/zonas/{id}` |
| Retirar (borrado lógico) | `DELETE /api/gestion/zonas/{id}` |

Ejemplo para `POST`:

```json
{
  "nombre": "Zona de prueba",
  "slug": "zona-de-prueba",
  "nivel_riesgo": "medio",
  "estado": "activa",
  "municipio": "El Espinal",
  "departamento": "Tolima",
  "pais": "Colombia",
  "latitud": 4.1486,
  "longitud": -74.8878,
  "is_demo": true
}
```

Ejemplo para `PATCH`:

```json
{ "nivel_riesgo": "alto" }
```

Una zona con sensores asociados devuelve `409` al intentar retirarla. La zona no se borra físicamente.

## Sensores

| Operación | Solicitud |
| --- | --- |
| Listar | `GET /api/gestion/sensores` |
| Consultar uno | `GET /api/gestion/sensores/{id}` |
| Crear | `POST /api/gestion/sensores` |
| Actualizar completa o parcialmente | `PUT` o `PATCH /api/gestion/sensores/{id}` |
| Retirar (borrado lógico) | `DELETE /api/gestion/sensores/{id}` |

El ejemplo usa IDs existentes de tipo y zona; sustitúyelos por los valores de tu base de datos:

```json
{
  "tipo_sensor_id": 1,
  "zona_id": 1,
  "codigo": "SEN-PRUEBA-01",
  "nombre": "Sensor de nivel de prueba",
  "unidad_medida": "cm",
  "estado": "sin_conexion",
  "intervalo_lectura": 60,
  "modelo": null,
  "fabricante": null,
  "umbral_minimo": null,
  "umbral_maximo": null,
  "fecha_instalacion": null,
  "is_demo": true
}
```

`ultima_medicion_at` y `ultima_conexion_at` se actualizan desde la telemetría y no se aceptan en la administración. El borrado del sensor es lógico para conservar mediciones, eventos y alertas.

## Alertas, mediciones e historial

- Alertas: `GET /api/gestion/alertas`, `GET /api/gestion/alertas/{id}` y `PATCH /api/gestion/alertas/{id}` con `{"estado":"reconocida"}` o `{"estado":"resuelta"}`. El cambio genera un evento de historial. Requiere `alertas.gestionar`.
- Mediciones: `GET /api/gestion/mediciones` y `GET /api/gestion/mediciones/{id}`. Son registros históricos; no admiten actualización o borrado administrativo.
- Eventos: `GET /api/gestion/eventos` y `GET /api/gestion/eventos/{id}`. Son de solo lectura para preservar la trazabilidad.
- Ingreso de mediciones IoT existente: `POST /api/iot/measurements`, con token Bearer del dispositivo y cuerpo definido por el contrato del controlador IoT. No usa la sesión del navegador.

Las validaciones devuelven `422` con el detalle por campo. Fuera del modo local sin sesión, una falta de sesión devuelve `401` y un permiso insuficiente `403`; un ID inexistente devuelve `404`.
