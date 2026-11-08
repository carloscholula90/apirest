---
name: siaweb-api
description: Cómo crear, corregir, probar y preparar para subir APIs de apirest (Laravel) de SIAWEB. Úsalo siempre que haya que tocar controladores, modelos o rutas de apirest, crear un endpoint nuevo, revisar por qué una ruta falla, validar contra la base siaweb_pruebas o armar la lista de archivos para subir a api.pruebas.siaweb.com.mx, aunque el usuario solo diga "crea la API", "valida las APIs faltantes" o "qué subo al servidor".
---

# APIs de apirest (SIAWEB)

apirest es el backend Laravel de SIAWEB. Las pantallas de `/secciones` lo consumen por HTTP. Este skill resume convenciones del proyecto y trampas ya comprobadas, para no repetir errores que costaron tiempo.

## Antes de escribir código: verifica el esquema real

Los modelos y migraciones del repositorio **no coinciden con la base real**. Ejemplo comprobado: el modelo `Horario` decía columnas por día (`horalIni`...) y la tabla real `horarios` guarda una fila por sesión. Diseñar desde el modelo produjo una API que no funcionaba.

- La conexión por defecto (`config/database.php`, sin `.env`) apunta a `siaweb_pruebas`. La configuración está cacheada en `bootstrap/cache/config.php`.
- Consulta columnas con un script PHP de solo lectura que arranque Laravel (`SHOW COLUMNS FROM ...`). Ver `references/esquema-verificado.md` para lo que ya se comprobó.
- El servidor MySQL distingue mayúsculas en nombres de tabla: `duracionSesion`, `diasFestivos`, `dtlTurno`, `horarioProfesor`.
- Nunca toques `siaweb_produccion` ni otras bases que el usuario pueda ver con esas credenciales.
- `config/database.php` trae la contraseña escrita como valor por defecto: no la imprimas ni la copies a ningún archivo o mensaje.

## Convenciones del proyecto

- **Rutas:** `routes/api.php` agrupa por prefijo y hace `require` de `routes/<modulo>/<recurso>.php`. Un recurso nuevo = archivo de rutas nuevo + bloque `Route::prefix(...)` en `api.php`, en orden alfabético.
- **Forma de las rutas:** los datos que identifican el recurso van en la ruta, no en query string: `GET /recurso/{idNivel}/{idPeriodo}`, `DELETE /recurso/{id}`. El alta suele ser `POST /recurso/create` o `POST /recurso`; sigue la que ya use el módulo. Los filtros opcionales sí pueden ir como query string (`?idTurno=`).
- **Orden de rutas:** declara rutas fijas (`/vista-previa`, `/activos`) antes de las que tienen parámetros (`/{id}`). Usa `->whereNumber()` cuando el parámetro sea numérico.
- **Controladores:** `App\Http\Controllers\Api\<modulo>\XController` extiende `App\Http\Controllers\Controller`. La ruta del archivo debe coincidir con el namespace (PSR-4); un archivo en otra carpeta no carga y la ruta da error.
- **Respuestas:** usa los helpers del controlador base.
  - Éxito con datos: `return $this->returnData('clave', $datos, 200);`
  - Mensaje o error: `return $this->returnEstatus('Mensaje', 400, $errores);`
- **Validación:** `Validator::make(...)`; si falla, `returnEstatus('Error en la validacion de los datos', 400, $validator->errors())`.
- **Errores de BD:** captura `QueryException`; el código `23000` significa llave duplicada o registro en uso.
- **Lógica compartida:** reutiliza traits existentes, como `Api\escolar\Concerns\ManejaHorarios` (días 1 = domingo … 7 = sábado, empalmes de horas).
- **Comentarios:** en español, cortos, explicando el porqué. Sigue el estilo del archivo que edites.

## Trampas comprobadas

- **Llave de `grupos`:** el modelo `Grupo` declara `grupoSec`, pero el código consulta `gruposec`. No uses `$grupo->update()` ni `refresh()` sobre un modelo obtenido por consulta; usa `Grupo::where('gruposec', $id)->update([...])` o `DB::table('grupos')`, que no dependen de la llave.
- **`$fillable`:** si un campo no está en `$fillable`, `update()` del modelo lo ignora sin avisar. Revisa al agregar columnas.
- **Campos opcionales:** si un campo no llega en la petición, no lo sobrescribas con `null`. Usa `array_merge($base, $request->only([...]))`.
- **`CONCAT` con nulos:** en MySQL devuelve `NULL` si un apellido es nulo. Arma nombres en PHP o con `CONCAT_WS`.
- **Textos con `\r`:** `turno.descripcion` y `nivel.descripcion` traen `\r` al final; aplica `trim`.
- **Catálogos con formatos raros:** `GET /periodos/{idNivel}` responde con la clave `$periodos`, y `GET /carreras` solo devuelve carreras que tienen plan (`JOIN plan`).
- **Uniones que duplican:** `horarios` tiene varias filas por grupo; usa subconsultas (`selectSub`) en lugar de `leftJoin` cuando solo necesites contar o saber si existe.

## Probar

1. **Sintaxis:** `C:/xampp/php/php.exe -l <archivo>`.
2. **Pruebas de feature en SQLite en memoria:** crea en `setUp` solo las tablas que usa el endpoint, **con las columnas reales**, y cambia la conexión con `config([...])` + `DB::purge(...)`. Sigue el ejemplo de `tests/Feature/OfertaAsignaturasApiTest.php`. Corre con:
   ```bash
   C:/xampp/php/php.exe vendor/bin/phpunit tests/Feature/<Archivo>Test.php
   ```
   `ExampleTest` falla por falta de `APP_KEY`; no es un error tuyo.
3. **Contra `siaweb_pruebas`:** usa un script que llame al kernel HTTP de Laravel dentro de `DB::beginTransaction()` … `DB::rollBack()`, comprobando antes que `SELECT DATABASE()` sea `siaweb_pruebas`. Así pruebas escrituras reales sin dejar datos. No imprimas nombres de personas. Si el usuario pidió no escribir en la base, esto también cuenta como escritura: quédate en lecturas y en SQLite, y anota qué quedó sin probar en MySQL.
4. **Si el usuario pide cargar datos de prueba**, hazlo solo en `siaweb_pruebas`, solo las filas que falten y verifica conteos después.
5. **Servidores reales (`api.pruebas.siaweb.com.mx`, `siaweb.com.mx`):** contra ellos solo haz peticiones GET. Un POST, PUT o DELETE cambia datos compartidos con otras personas y no se puede revertir como una transacción local, así que hazlo solo si el usuario lo autoriza en ese momento. Para probar escrituras usa SQLite o la transacción con `ROLLBACK` del punto 3.
6. **Antes de crear un recurso, busca si ya existe** (`routes/`, `app/Http/Controllers`, `app/Models`). Si existe y está roto, corrígelo en su lugar en vez de duplicarlo, y avisa si el cambio altera algo que otras pantallas usan.

`php artisan route:list` hoy falla por un controlador inexistente (`RolSegController`), así que comprueba las rutas con las pruebas.

## Preparar la subida

Entrega una lista, no subas nada tú.

1. Ordénala así: modelos → traits → controladores → archivos de rutas del módulo → bloques nuevos de `routes/api.php` → archivos a borrar → caché → verificación con `curl`.
2. **No pidas subir `routes/api.php` completo:** la copia local suele traer cambios ajenos (rutas que cargan archivos que quizá no existen en el servidor, lo que tumba toda la API). Da solo los bloques `Route::prefix(...)` a agregar.
3. Revisa con `git status` y `git diff` qué archivos tocaste. Si un archivo trae cambios previos del usuario, avísalo, porque se subirán juntos.
4. Excluye `tests/`, `.claude/` y archivos que no sean del trabajo.
5. Incluye `php artisan route:clear` (por si el servidor usa caché de rutas) y un `curl` por endpoint nuevo con la respuesta esperada.

No hagas commit salvo que el usuario lo pida.
