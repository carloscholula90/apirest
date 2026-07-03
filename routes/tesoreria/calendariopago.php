<?php

use App\Http\Controllers\Api\tesoreria\CalendarioPagoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CalendarioPagoController::class, 'index']);
Route::get('/{idTurno}', [CalendarioPagoController::class, 'show']);
Route::post('/create', [CalendarioPagoController::class, 'store']);
Route::put('/{idTurno}', [CalendarioPagoController::class, 'update']);
Route::delete('/{idTurno}', [CalendarioPagoController::class, 'destroy']);
