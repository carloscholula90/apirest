<?php
use App\Http\Controllers\Api\escolar\CarreraController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [CarreraController::class, 'index']);
Route::get('/{idCarrera}', [CarreraController::class, 'show']);
Route::post('/create', [CarreraController::class, 'store']);
Route::patch('/{idCarrera}', [CarreraController::class, 'updatePartial']);
Route::delete('/{idCarrera}', [CarreraController::class, 'destroy']);
Route::get('/disponibles/{idNivel}/{uid}', [CarreraController::class, 'disponiblesAlumno']);
Route::get('/semestresAnteriores/{idNivel}/{uid}/{idCarrera}', [CarreraController::class, 'semestresAnteriores']);

Route::post('/generaReporte', [CarreraController::class, 'generaReporte']);
Route::post('/imprimeXls', [CarreraController::class, 'exportaExcel']); 
  