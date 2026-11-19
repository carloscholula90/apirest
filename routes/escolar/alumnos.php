<?php
use App\Http\Controllers\Api\escolar\AlumnoController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::get('/avance/{uid}/{secuencia}', [AlumnoController::class, 'getAvance']);  
Route::post('/actualizaMonto', [AlumnoController::class, 'actualizaMonto']);
Route::post('/bajas', [AlumnoController::class, 'bajaAlumno']);
Route::put('/matriculaAnterior', [AlumnoController::class, 'asignaMatriculaAnterior']);
Route::get('/alumnosInscritos/{idNivel}/{idPeriodo}', [AlumnoController::class, 'alumnosInscritosConcentrado']);
Route::get('/alumnosInscritosExc/{idNivel}/{idPeriodo}', [AlumnoController::class, 'exportExcelCocentrado']);
Route::get('/alumnosInscritosDtl/{idNivel}/{idPeriodo}', [AlumnoController::class, 'alumnosInscritosDetallado']);
Route::get('/alumnosInscritosDtlExc/{idNivel}/{idPeriodo}', [AlumnoController::class, 'alumnosInscritosDetalladoExc']);  
// Reporte de alumnos inscritos por rango de fechas (alumno.fechaAlta), en PDF y Excel
Route::get('/inscritosFechas/{fechaInicio}/{fechaFin}', [\App\Http\Controllers\Api\escolar\ReporteAlumnosInscritosController::class, 'pdf']);
Route::get('/inscritosFechasExc/{fechaInicio}/{fechaFin}', [\App\Http\Controllers\Api\escolar\ReporteAlumnosInscritosController::class, 'excel']);
Route::get('/{uid}', [AlumnoController::class, 'getAlumno']);
Route::get('/{uid}/reincorporacion', [\App\Http\Controllers\Api\escolar\ReincorporacionController::class, 'show']);
Route::post('/reincorporacion', [\App\Http\Controllers\Api\escolar\ReincorporacionRegistroController::class, 'store']);
