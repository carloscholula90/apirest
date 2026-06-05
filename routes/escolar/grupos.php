<?php
use App\Http\Controllers\Api\escolar\GrupoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::group([], function () {

    // Consultas
    Route::get('/', [GrupoController::class, 'index']);

    Route::get('/reporte', [
        GrupoController::class,
        'exportaExcel'
    ]);

    Route::get('/asignaturas/{grupo}', [
        GrupoController::class,
        'obtenerAsignaturas'
    ]);

    Route::get('/{idNivel}/{idPeriodo}/{idCarrera}', [
        GrupoController::class,
        'show'
    ]);

    // Actualizaciones
    Route::post('/grupo/{gruposec}', [
        GrupoController::class,
        'actualizarProfesor'
    ]);

    Route::post('/actas/{gruposec}', [
        GrupoController::class,
        'actualizarActas'
    ]);

});