# Ambiente local de SIAWEB

Sirve para probar las pantallas de `frontend/` con el código de apirest de este repositorio y una **copia local** de `siaweb_pruebas`. Lo que guardes aquí no llega a ningún servidor.

| Pieza | Dirección | Qué es |
|---|---|---|
| API | http://127.0.0.1:8000/api | apirest del repositorio contra `datos/siaweb_local.sqlite` |
| Pantallas | http://127.0.0.1:8001 | Layout simulado; abre `frontend/<nombre>/<nombre>.php` como `/secciones/<nombre>` |

## Uso

1. Doble clic en **`iniciar.bat`**. La primera vez copia la base (unos 20 segundos), abre dos ventanas de servidor y el navegador.
2. Elige la pantalla en la lista, por ejemplo `/secciones/oferta-asignaturas`.
3. Para detener, cierra las dos ventanas "SIAWEB local".

Los cambios en `frontend/` y en `app/` se ven al recargar la página, sin reiniciar.

Cada petición a la API queda en `datos/peticiones.log` (método, ruta, respuesta y, en escrituras o errores, los datos enviados y el mensaje). Sirve para ver qué hizo la pantalla cuando algo no funciona.

Para empezar de cero con los datos actuales del servidor, cierra las ventanas y corre **`actualizar-datos.bat`**.

## Qué se copia

`copiar-datos.php` solo **lee** de `siaweb_pruebas` (se detiene si la conexión apunta a otra base) y copia las tablas de Oferta de asignaturas y del esquema de pago: niveles, periodos, carreras, planes, asignaturas, grupos, horarios, turnos y su detalle, bloques del turno, duración de sesión, días festivos, personas, empleados y catálogos de régimen fiscal y pagos.

- Los firmantes predeterminados del acta salen de `configuracionActas` (una fila por nivel). La tabla `configuracion` no se copia.
- La copia incluye datos personales (`persona`). Está en `datos/`, que git ignora: no la compartas ni la subas.
- Si una pantalla usa una tabla que no está en la lista, agrégala en `$tablas` de `copiar-datos.php` y vuelve a copiar.

## Diferencias con el servidor

- La base es SQLite. Las funciones de MySQL que usan los controladores (`CONCAT`, `CONCAT_WS`, `IF`) están emuladas; si aparece un error de SQL que en el servidor no sale, puede ser por eso.
- El layout es simulado: no hay menú, sesión, Hope UI ni `crud/utils.php` reales, y el botón Excel no funciona.
- Las imágenes de `/secciones/img/...` (logotipos) se cargan de pruebas.siaweb.com.mx.
