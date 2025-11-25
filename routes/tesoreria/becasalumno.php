<?php
use App\Http\Controllers\Api\tesoreria\BecasAlumnoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [BecasAlumnoController::class, 'index']);
Route::post('/generaReporte', [BecasAlumnoController::class, 'generaReporte']);   
Route::post('/imprimeXls', [BecasAlumnoController::class, 'exportaExcel']);
Route::get('/', [BecasAlumnoController::class, 'index']);
Route::post('/create', [BecasAlumnoController::class, 'store']);
Route::put('/', [BecasAlumnoController::class, 'update']);
Route::delete('/{idNivel}/{idPeriodo}/{uid}/{secuencia}', [BecasAlumnoController::class, 'destroy']);
