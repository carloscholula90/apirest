<?php

use App\Http\Controllers\Api\escolar\DefaultTurnoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DefaultTurnoController::class, 'index']);
Route::get('/{idTurno}', [DefaultTurnoController::class, 'show']);
Route::post('/create', [DefaultTurnoController::class, 'store']);
Route::put('/{idTurno}/{idTipoBloque}', [DefaultTurnoController::class, 'update']);
Route::delete('/{idTurno}/{idTipoBloque}', [DefaultTurnoController::class, 'destroy']);
Route::post('/imprimeXls', [DefaultTurnoController::class, 'exportaExcel']);
Route::post('/generaReporte', [DefaultTurnoController::class, 'generaReporte']);
