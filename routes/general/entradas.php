<?php

use App\Http\Controllers\Api\general\EntradaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EntradaController::class, 'index']);
Route::get('/{idEntrada}', [EntradaController::class, 'show']);
Route::post('/create', [EntradaController::class, 'store']);
Route::put('/{idEntrada}', [EntradaController::class, 'update']);
Route::delete('/{idEntrada}', [EntradaController::class, 'destroy']);
