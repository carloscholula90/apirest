<?php
use App\Http\Controllers\Api\general\PuestoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::post('/generaReporte', [PuestoController::class, 'generaReporte']);
Route::get('/', [PuestoController::class, 'index']);
Route::get('/{idPuesto}', [PuestoController::class, 'show']);
Route::post('/create', [PuestoController::class, 'store']);  
Route::put('/{idPuesto}', [PuestoController::class, 'update']);
Route::delete('/{idPuesto}', [PuestoController::class, 'destroy']);
Route::post('/imprimeXls', [PuestoController::class, 'exportaExcel']);  
  