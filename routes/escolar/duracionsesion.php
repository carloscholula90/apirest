<?php
use App\Http\Controllers\Api\escolar\DuracionSesionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DuracionSesionController::class, 'index']);
Route::get('/{idNivel}/{idTurno}', [DuracionSesionController::class, 'show'])
    ->whereNumber('idNivel')
    ->whereNumber('idTurno');
