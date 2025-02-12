<?php
use App\Http\Controllers\Api\general\AsentamientoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [AsentamientoController::class, 'index']);
Route::get('/{idAsentamiento}', [AsentamientoController::class, 'show']);
Route::post('/create', [AsentamientoController::class, 'store']);  
Route::put('/{idAsentamiento}', [AsentamientoController::class, 'update']);
Route::patch('/{idAsentamiento}', [AsentamientoController::class, 'updatePartial']);
Route::delete('/{idAsentamiento}', [AsentamientoController::class, 'destroy']);
Route::post('/imprimeXls', [AsentamientoController::class, 'exportaExcel']);  
  