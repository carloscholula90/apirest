<?php
use App\Http\Controllers\Api\general\TipoContratoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::post('/generaReporte', [TipoContratoController::class, 'generaReporte']);
Route::get('/', [TipoContratoController::class, 'index']);
Route::get('/{idTipoContrato}', [TipoContratoController::class, 'show']);
Route::post('/create', [TipoContratoController::class, 'store']);  
Route::put('/{idTipoContrato}', [TipoContratoController::class, 'update']);
Route::delete('/{idTipoContrato}', [TipoContratoController::class, 'destroy']);
Route::post('/imprimeXls', [TipoContratoController::class, 'exportaExcel']); 
  