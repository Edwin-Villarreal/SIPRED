# Recuperación segura de cuentas SIPRED

Este módulo permite restablecer una contraseña solo después de verificar un método de recuperación previamente generado por el usuario. Los códigos y el archivo de recuperación se administran desde **Cuenta → Recuperación de cuenta**.

## Generar y guardar los códigos

1. Inicia sesión y abre **Recuperación de cuenta** desde el menú del usuario.
2. Selecciona **Generar seis códigos nuevos**.
3. Guarda o descarga los seis códigos en un lugar seguro. SIPRED los muestra una sola vez y conserva únicamente hashes Laravel de cada código.
4. Al generar un nuevo conjunto, los códigos que todavía estaban disponibles quedan revocados. Los códigos ya usados no se vuelven a habilitar.

Cada código es aleatorio, independiente y se consume una sola vez, únicamente al completar un cambio de contraseña. Un código verificado que no complete el cambio no se marca como usado; su desafío vence en diez minutos.

## Generar y guardar el archivo oficial

En la misma página, elige **Generar y descargar archivo**. El navegador descarga `sipred-recovery.json`. Guárdalo en un medio privado y no lo compartas. Contiene un identificador aleatorio y una credencial secreta; la base de datos almacena solo el hash de esa credencial.

El archivo nuevo revoca el archivo anterior aún disponible. SIPRED no guarda la descarga en el directorio público del servidor. Durante la recuperación se valida el JSON, su versión, el identificador y la credencial, con límite de 8 KB. La extensión o el MIME no sustituyen la validación de contenido.

## Recuperar una contraseña

1. En la pantalla de inicio de sesión, abre **¿Olvidaste tu contraseña?**.
2. Escribe el correo asociado a la cuenta y verifica un código o carga el archivo oficial, según los métodos habilitados.
3. Si la verificación es correcta, establece y confirma la nueva contraseña en los siguientes diez minutos.
4. Vuelve al inicio de sesión y entra con la contraseña nueva.

Las respuestas ante credenciales incorrectas son genéricas para no revelar si una cuenta existe. Hay límites por IP y por método/correo. El desafío se conserva en la sesión, y su token se guarda como hash en la base de datos. No se aceptan IDs de usuario enviados desde el formulario.

El cambio se realiza dentro de una transacción. En ese momento se consume atómicamente el código o archivo y el desafío, se actualiza la contraseña mediante `Hash::make`, se rota el token de «recordarme» y se invalidan los demás desafíos activos. Si Laravel utiliza sesiones en base de datos, se eliminan las sesiones del usuario; con el driver de archivos actual, el middleware `auth.session` invalida las sesiones existentes cuando vuelvan a presentar la contraseña anterior.

## Configurar los métodos

Solo una cuenta con el permiso administrativo `recovery.settings` puede abrir **Configuración → Configuración de recuperación de cuenta**.

- **Permitir recuperación mediante códigos** controla la generación y verificación de códigos.
- **Permitir recuperación mediante archivo** controla la generación y verificación del archivo oficial.
- Debe quedar activo al menos uno de esos métodos. El servidor rechaza una solicitud que intente desactivar ambos.
- **Restablecimiento de contraseña** permanece siempre activado y no es una preferencia editable. Esto no omite la verificación: siempre se exige completar un método habilitado antes de cambiar una contraseña.

El seeder `RecoverySettingsSeeder` inicializa ambas opciones activadas con `firstOrCreate`, por lo que no reemplaza las decisiones administrativas al ejecutarse otra vez.

## Protección de datos y eventos

Las contraseñas se guardan con el hasher de Laravel. Los códigos, la credencial del archivo y los tokens de desafío no se guardan en texto plano. La bitácora registra eventos de generación, cambios de configuración y restablecimientos, sin incluir códigos, archivos, contraseñas ni tokens.

Las tablas de recuperación se agregan mediante una migración incremental y sus registros se relacionan con usuarios mediante claves foráneas. La base de datos principal sigue usando MySQL/InnoDB.

## Ejecutar las pruebas

Desde la raíz del proyecto:

```powershell
php artisan test
pnpm build
```

PHPUnit usa la conexión SQLite en memoria definida en `phpunit.xml`; las pruebas no necesitan modificar la base de datos MySQL de desarrollo. Para aplicar la migración en una instalación existente, usa `php artisan migrate` y después, si aún no se ha inicializado la preferencia y el permiso, `php artisan db:seed --class=RecoverySettingsSeeder`. No uses `migrate:fresh` en una base con datos.
