<?php

use App\Http\Controllers\Api\nomina\PagoController;
use Illuminate\Support\Facades\Route;

Route::get('/concentrado/{idCalendarioPago}', [PagoController::class, 'concentrado']);
Route::post('/procesar/{idCalendarioPago}', [PagoController::class, 'procesar']);
