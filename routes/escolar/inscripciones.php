<?php

use App\Http\Controllers\Api\escolar\InscripcionesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group([], function () {
     Route::post('/create', [InscripcionesController::class, 'store']);
    Route::post('/create-becados', [InscripcionesController::class, 'createBecados']);
    Route::post('/registrar', [InscripcionesController::class, 'registrarInscripcion']);
    Route::get('/{idNivel}', [InscripcionesController::class, 'index'])
        ->where('idNivel', '[0-9]+');
});