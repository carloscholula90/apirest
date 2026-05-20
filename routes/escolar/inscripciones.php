<?php

use App\Http\Controllers\Api\escolar\InscripcionesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group([], function () {
     Route::post('/create', [InscripcionesController::class, 'store']);
    Route::post('/create-becados', [InscripcionesController::class, 'createBecados']);
    Route::get('/{idNivel}', [InscripcionesController::class, 'index'])
        ->where('idNivel', '[0-9]+');
});