<?php

use App\Http\Controllers\Api\nomina\CalendarioPagoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CalendarioPagoController::class, 'index']);
Route::get('/siguiente', [CalendarioPagoController::class, 'siguiente']);
Route::get('/turno/{idTurno}', [CalendarioPagoController::class, 'porTurno']);
Route::get('/{idCalendarioPago}', [CalendarioPagoController::class, 'show']);
Route::post('/create', [CalendarioPagoController::class, 'store']);
Route::put('/{idCalendarioPago}', [CalendarioPagoController::class, 'update']);
Route::delete('/{idCalendarioPago}', [CalendarioPagoController::class, 'destroy']);
