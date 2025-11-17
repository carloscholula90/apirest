<?php
use App\Http\Controllers\Api\tesoreria\ConfiguracionesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::post('/generaReporte', [ConfiguracionesController::class, 'generaReporte']);   
Route::post('/imprimeXls', [ConfiguracionesController::class, 'exportaExcel']);
Route::get('/', [ConfiguracionesController::class, 'index']);
Route::post('/create', [ConfiguracionesController::class, 'store']);
Route::put('/', [ConfiguracionesController::class, 'update']);
Route::delete('/{idNivel}', [ConfiguracionesController::class, 'destroy']);
