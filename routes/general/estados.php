<?php
use App\Http\Controllers\Api\general\EstadoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::post('/imprimeXls', [EstadoController::class, 'exportaExcel']);  
Route::post('/generaReporte', [EstadoController::class, 'generaReporte']);
Route::get('/', [EstadoController::class, 'index']);
Route::get('/{idPais}/{idEstado}', [EstadoController::class, 'show']);
Route::post('/create', [EstadoController::class, 'store']);  
Route::put('/', [EstadoController::class, 'update']);
Route::delete('/{idPais}/{idEstado}', [EstadoController::class, 'destroy']);
      