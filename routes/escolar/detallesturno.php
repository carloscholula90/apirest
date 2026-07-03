<?php

use App\Http\Controllers\Api\escolar\DetalleTurnoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DetalleTurnoController::class, 'index']);
Route::get('/{idTurno}', [DetalleTurnoController::class, 'show']);
Route::post('/create', [DetalleTurnoController::class, 'store']);
Route::put('/{idDtlTurno}', [DetalleTurnoController::class, 'update']);
Route::delete('/{idTurno}/{idDtlTurno}', [DetalleTurnoController::class, 'destroy']);
Route::post('/imprimeXls', [DetalleTurnoController::class, 'exportaExcel']);
Route::post('/generaReporte', [DetalleTurnoController::class, 'generaReporte']);
