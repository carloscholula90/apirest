<?php
use App\Http\Controllers\Api\escolar\AlumnoController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::get('/avance/{uid}/{secuencia}', [AlumnoController::class, 'getAvance']);  
Route::post('/actualizaMonto', [AlumnoController::class, 'actualizaMonto']);
Route::post('/bajas', [AlumnoController::class, 'bajaAlumno']);
Route::get('/alumnosInscritos/{idNivel}/{idPeriodo}', [AlumnoController::class, 'alumnosInscritosConcentrado']);
Route::get('/alumnosInscritosExc/{idNivel}/{idPeriodo}', [AlumnoController::class, 'exportExcelCocentrado']);
Route::get('/alumnosInscritosDtl/{idNivel}/{idPeriodo}', [AlumnoController::class, 'alumnosInscritosDetallado']);
Route::get('/alumnosInscritosDtlExc/{idNivel}/{idPeriodo}', [AlumnoController::class, 'alumnosInscritosDetalladoExc']);  
Route::get('/{uid}', [AlumnoController::class, 'getAlumno']);
