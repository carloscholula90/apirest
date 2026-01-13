<?php
use App\Http\Controllers\Api\escolar\AlumnoController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::get('/avance/{uid}/{secuencia}', [AlumnoController::class, 'getAvance']);  
Route::get('/{uid}', [AlumnoController::class, 'getAlumno']);
Route::get('/alumnosInscritos/{idNivel}/{idPeriodo}', [AlumnoController::class, 'alumnosInscritosConcentrado']);
Route::get('/alumnosInscritosExc/{idNivel}/{idPeriodo}', [AlumnoController::class, 'exportExcelCocentrado']);
Route::get('/alumnosInscritosDtl/{idNivel}/{idPeriodo}', [AlumnoController::class, 'alumnosInscritosDetallado']);
  