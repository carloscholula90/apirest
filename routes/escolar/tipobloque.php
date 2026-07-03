<?php

use App\Http\Controllers\Api\escolar\TipoBloqueController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TipoBloqueController::class, 'index']);
Route::get('/{idTipoBloque}', [TipoBloqueController::class, 'show']);
Route::post('/create', [TipoBloqueController::class, 'store']);
Route::put('/{idTipoBloque}', [TipoBloqueController::class, 'update']);
Route::delete('/{idTipoBloque}', [TipoBloqueController::class, 'destroy']);
Route::post('/imprimeXls', [TipoBloqueController::class, 'exportaExcel']);
Route::post('/generaReporte', [TipoBloqueController::class, 'generaReporte']);
