<?php
use App\Http\Controllers\Api\escolar\ConfiguracionActasController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ConfiguracionActasController::class, 'index']);
Route::get('/{idNivel}', [ConfiguracionActasController::class, 'show'])->whereNumber('idNivel');
Route::put('/{idNivel}', [ConfiguracionActasController::class, 'update'])->whereNumber('idNivel');
Route::delete('/{idNivel}', [ConfiguracionActasController::class, 'destroy'])->whereNumber('idNivel');
