# Entorno de prueba para pantallas de /secciones

Prueba una pantalla completa (front + apirest) sin tocar `siaweb_pruebas` ni producción.

| Archivo | Para qué sirve |
|---|---|
| `sembrar.php` | Crea `prueba.sqlite` con datos ficticios. Trae las tablas de Oferta de asignaturas; agrega las que use tu pantalla, **con las columnas reales** (ver el skill `siaweb-api`). |
| `router-api.php` | Sirve apirest usando `prueba.sqlite`. |
| `router-pagina.php` | Simula el layout (jQuery, Bootstrap, iconos, fuente) e incluye `frontend/<nombre>/<nombre>.php`. |
| `stubs/crud/` | Versiones vacías de `crud/utils.php` y `crud/get.php`. |

## Pasos

1. Siembra la base (bórrala y vuelve a sembrar antes de cada corrida, para empezar limpio):
   ```bash
   C:/xampp/php/php.exe .claude/skills/siaweb-secciones/scripts/prueba/sembrar.php
   ```
2. Agrega temporalmente a `.claude/launch.json`:
   ```json
   { "name": "pantalla-api-temporal", "runtimeExecutable": "C:/xampp/php/php.exe",
     "runtimeArgs": ["-S", "127.0.0.1:8000", "C:/xampp/htdocs/apirest/.claude/skills/siaweb-secciones/scripts/prueba/router-api.php"], "port": 8000 },
   { "name": "pantalla-pagina-temporal", "runtimeExecutable": "C:/xampp/php/php.exe",
     "runtimeArgs": ["-S", "127.0.0.1:8001", "C:/xampp/htdocs/apirest/.claude/skills/siaweb-secciones/scripts/prueba/router-pagina.php"], "port": 8001 }
   ```
3. Inicia ambos con `preview_start` y abre `http://127.0.0.1:8001/secciones/<nombre>`.
4. Corre la batería con `javascript_tool`:
   - Espera primero a que cargue: `document.readyState === 'complete'` y que el select principal ya tenga sus eventos (`$._data(el, 'events')`).
   - Usa una función `hasta(condicion, ms)` que sondee cada 100 ms en lugar de esperas fijas.
   - Para cambiar campos usa `$(el).trigger('input').trigger('change')`, porque la pantalla escucha con jQuery.
   - Registra cada comprobación como OK o FALLA con el valor obtenido, y regresa solo las fallas.
5. Si algo falla, distingue entre un defecto de la pantalla y uno del entorno (tabla que falta en `sembrar.php`, esperas cortas) revisando `storage/logs/laravel.log` y `preview_logs`.
6. Al terminar: `preview_stop` en ambos servidores y quita las entradas de `launch.json`.
