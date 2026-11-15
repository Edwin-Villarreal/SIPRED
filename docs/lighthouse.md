# Evaluación de SIPRED con Google Lighthouse

## Alcance y estado

Google Lighthouse integrado en Chrome es la herramienta recomendada para evaluar Performance, Accessibility, Best Practices y SEO. No se instalan axe DevTools ni extensiones de auditoría adicionales.

No hay puntuaciones Lighthouse registradas para esta entrega. No se debe afirmar que SIPRED aprobó una auditoría hasta ejecutar el procedimiento descrito aquí y guardar los informes generados por Chrome.

SIPRED es una aplicación privada detrás de inicio de sesión. Las páginas de acceso y las vistas operativas llevan la instrucción `noindex, nofollow, noarchive`; por ello, Lighthouse puede señalar que no deben indexarse. En este caso es una decisión intencional para proteger la naturaleza privada de la plataforma. Si se necesita SEO público, debe crearse una página pública independiente.

## Preparar el entorno

1. En la raíz del proyecto, compila los recursos instalados sin añadir paquetes:

   ```powershell
   npm run build
   ```

2. Inicia Laravel y deja MySQL disponible con la base de pruebas o DEMO que se utilizará durante la evaluación:

   ```powershell
   php artisan serve --host=127.0.0.1 --port=8000
   ```

3. Abre Google Chrome en `http://127.0.0.1:8000/login`. Para las rutas privadas, inicia sesión con una cuenta de evaluación. No uses cuentas de producción ni guardes credenciales en el informe.
4. Deja fija la misma fuente de datos durante las comparaciones. Anota si la base contiene datos DEMO, datos reales o una mezcla.

Lighthouse ejecuta navegación y puede volver a cargar la página. No uses una página con un formulario sin guardar ni realices cambios administrativos durante una corrida.

## Ejecutar Lighthouse en Chrome

1. Abre la ruta que quieres revisar. Empieza por `/login`; después, con sesión de evaluación, revisa `/dashboard`, `/sensores`, `/mediciones`, `/alertas`, `/eventos`, `/reportes` y `/mapa-riesgos`.
2. Abre las herramientas de desarrollador de Chrome con `F12` o `Ctrl+Shift+I` y selecciona la pestaña **Lighthouse**. Si está dentro del menú de pestañas, ábrela desde el menú `»`.
3. Selecciona el modo **Navigation** y el dispositivo que quieres medir. Ejecuta corridas separadas para móvil y escritorio; no compares resultados de modos distintos.
4. Marca **Performance**, **Accessibility**, **Best Practices** y **SEO**. Inicia el análisis con **Analyze page load** o el botón equivalente de la versión instalada de Chrome.
5. Lee las oportunidades y los diagnósticos. Abre cada elemento afectado para identificar la página, recurso o control que lo origina.
6. Guarda el informe desde el menú de descarga de Lighthouse en formato HTML. Cuando la versión de Chrome lo permita, guarda también JSON para conservar los datos estructurados.

## Guardar y nombrar los informes

Antes de una sesión, crea una carpeta por fecha desde la raíz del proyecto:

```powershell
$reportFolder = Join-Path 'outputs/lighthouse' (Get-Date -Format 'yyyy-MM-dd')
New-Item -ItemType Directory -Force $reportFolder | Out-Null
```

Guarda los informes bajo `outputs/lighthouse/AAAA-MM-DD/`, separados por página y dispositivo. Usa nombres sin datos personales, por ejemplo:

```text
outputs/lighthouse/2026-11-12/login-mobile-20261112-1530.html
outputs/lighthouse/2026-11-12/dashboard-desktop-20261112-1545.html
outputs/lighthouse/2026-11-12/dashboard-desktop-20261112-1545.json
```

Con cada corrida anota en un registro `README.md` o una hoja de seguimiento:

- fecha y hora local;
- URL y módulo;
- dispositivo, modo y configuración de limitación de red/CPU;
- versiones de Chrome y Lighthouse;
- estado de autenticación y tipo de datos usados, sin incluir usuarios ni credenciales;
- puntuaciones observadas y hallazgos prioritarios;
- cambios de código vinculados a la corrida.

Las puntuaciones pertenecen al informe real exportado. Si no se ejecutó una corrida, déjala como pendiente; nunca rellenes valores estimados.

## Corregir y volver a evaluar

1. Registra cada hallazgo con su categoría, URL, recurso o componente, efecto y recomendación que muestra Lighthouse.
2. Prioriza los problemas que bloquean contenido visible, recursos grandes, cambios de layout, controles sin nombre accesible y errores de consola o de navegador.
3. Corrige el origen en Blade, CSS, JavaScript o la configuración correspondiente. Mantén las etiquetas DEMO y los avisos de datos sintéticos.
4. Ejecuta `npm run build` después de cambiar recursos frontend. Comprueba la página y la interacción afectadas antes de repetir la medición.
5. Repite Lighthouse con la misma URL, dispositivo y configuración. Guarda el nuevo informe junto al anterior y describe qué hallazgo se resolvió o sigue presente.
6. No ocultes avisos con CSS ni retires controles o funciones para subir una puntuación. Si una regla no aplica, documenta por qué y conserva el informe.

Para reducir variación, ejecuta tres corridas por combinación de página y dispositivo y registra las tres. Compara tendencias bajo condiciones iguales; Chrome, el estado del servidor, la red y la base pueden afectar los resultados.

## Cobertura mínima recomendada

| Página o flujo | Enfoque adicional |
| --- | --- |
| Login y recuperación | Etiquetas, relación de errores con campos, teclado y etiquetas SEO privadas. |
| Dashboard | Carga inicial, gráfica SVG, navegación y skeleton de transición. |
| Listados de sensores, mediciones, alertas y eventos | Búsqueda, filtros GET, paginación, tablas y skeleton durante navegación. |
| Formularios de zona y sensor | Labels, errores de validación, foco, controles y envío por teclado. |
| Vista de mapa de riesgos y reportes | Tablas, lectura de datos y estado DEMO; el mapa geográfico sigue pendiente. |
| Móvil y escritorio | Navegación lateral, foco visible, reflujo y objetivos táctiles. |

Lighthouse solo evalúa la URL y el estado visibles en cada corrida. Una evaluación de una página no representa automáticamente a los demás módulos.
