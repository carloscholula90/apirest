# Esquema verificado en siaweb_pruebas

Comprobado el 4 y 7 de noviembre de 2026 con `SHOW COLUMNS`. Vuelve a verificar si algo no cuadra.

## grupos
Un renglón por asignatura y grupo. PK `grupoSec`.
`grupoSec, idNivel, idPeriodo, idAsignatura, grupo (varchar 8), uidProfesor, secuenciaProfesor, inscritos, capacidad, idIdioma, uidSecretario, uidPresidente, uidSupervisor, idFormato, fechaIni (date), horaIni/horaFin (varchar 8), idTurno, uidMto, logoSep/logoEscudo (varchar 60), fechaAlta (not null), fechaModificacion`

- La carrera son los primeros 1 o 2 caracteres de `grupo` (`06M1A` → carrera 6, turno M, semestre 1).
- Semestre: `SUBSTRING(grupo, CASE WHEN LENGTH(grupo) = 4 THEN 3 ELSE 4 END, 1)`.

## horarios
Una fila por sesión de clase. PK compuesta (`secHorarios` autoincremental, `grupoSec`). FK `grupoSec` → `grupos.grupoSec`.
`secHorarios, grupoSec, fechaIni (datetime), fechaFin (datetime), day (tinyint, 1 = domingo … 7 = sábado), impartido (tinyint), fechaAlta, fechaModificacion`

- Las sesiones impartidas (`impartido = 1`) no se modifican ni se borran.

## duracionSesion
`idNivel, idTurno, duracion (minutos)`. Una fila por nivel y turno. Los datos de `siaweb_pruebas` son de prueba (50 minutos para todo).

## configuracion
`id_campo (PK), valor, descripcion`
- `id_campo` 1: estado de cuenta; 2: tipos de entradas.
- `id_campo` 3, 4, 5: secretario, presidente y supervisor predeterminados del acta. `valor` trae el rol y `descripcion` el **nombre** de la persona, sin uid. Cada firmante vacío del acta toma este valor, campo por campo.

## periodo
`idNivel, idPeriodo, descripcion, activo, inscripciones, cerrado, fechaInicio, fechaTermino, inmediato, logoGob, ...`

## diasFestivos
`fechaFestivo (date), descripcion (varchar 30), fechaAlta, fechaModificacion`. Sin llave primaria ni índice único: se identifica por `fechaFestivo` y hay que evitar duplicados en el código. Al generar sesiones se omiten estas fechas.

- Estado encontrado el 7 de noviembre de 2026: `GET /diasfestivos/{year}` (`DiasFestivosController::getHolidays`) inserta los festivos oficiales de Yasumi en cada llamada, así que duplica registros. `store` y `destroy` del mismo controlador no tienen ruta, les faltan los `use` de `Validator` y `QueryException`, y `destroy` usa `find()` sobre un modelo sin llave.

## formato
`idFormato, nombre, ruta, default`. Vacía en `siaweb_pruebas`.

## Otras
- `turno`: `idTurno (0 = TODOS), descripcion, letra, parciales`.
- `dtlTurno`: horario del turno por día (`diaSemana`, `horaInicio`, `horaFin`).
- `horarioProfesor`: `uid, secuencia, fechaInicio, fechaFin, diaSemana, horaInicio, horaFin, idTipoBloque`.
- `persona`: `uid, nombre, primerApellido, segundoApellido`; `empleado`: `uid, secuencia, fechabaja`.
