<?php
use App\Http\Controllers\Api\escolar\GrupoController;
use App\Http\Controllers\Api\escolar\GruposOfertaController;
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

    Route::get('/semestres/{idNivel}/{idPeriodo}/{idCarrera}', [
        GrupoController::class,
        'gruposSemestre'
    ]);

    Route::get('/semestres/{idNivel}/{idPeriodo}/{idCarrera}/{idTurno}', [
        GrupoController::class,
        'gruposSemestrePorTurno'
    ]);

    Route::get('/alumnos/{idNivel}/{idPeriodo}/{grupo}', [
        GrupoController::class,
        'alumnosInscritosGrupo'
    ]);

    Route::get('/profesor/{idPeriodo}/{uidProfesor}/{secuenciaProfesor?}', [
        GrupoController::class,
        'gruposProfesor'
    ]);

    Route::get('/asignatura/{idNivel}/{idPeriodo}/{idCarrera}/{idAsignatura}', [
        GrupoController::class,
        'gruposAsignatura'
    ])
    ->whereNumber('idNivel')
    ->whereNumber('idPeriodo')
    ->whereNumber('idCarrera');

    Route::get('/relacionados/{grupo}', [
        GrupoController::class,
        'gruposRelacionados'
    ]);

    Route::get('/{idNivel}/{idPeriodo}/{idCarrera}', [
        GrupoController::class,
        'show'
    ]);

    // Actualizaciones
    Route::post('/cambioGrupo', [
        GrupoController::class,
        'cambioGrupo'
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

Route::get('/consulta/{idNivel}/{idPeriodo}/{idCarrera}', [\App\Http\Controllers\Api\escolar\GruposConsultaController::class, 'show']);

// Oferta de asignaturas
Route::get('/oferta/{idNivel}/{idPeriodo}/{idCarrera}', [GruposOfertaController::class, 'index'])
    ->whereNumber('idNivel')
    ->whereNumber('idPeriodo')
    ->whereNumber('idCarrera');
Route::get('/actas/{gruposec}', [GruposOfertaController::class, 'acta']);
Route::put('/capacidad/{gruposec}', [GruposOfertaController::class, 'actualizarCapacidad']);
Route::put('/logotipo/{gruposec}', [GruposOfertaController::class, 'actualizarLogotipo']);
Route::delete('/grupo/{gruposec}', [GruposOfertaController::class, 'destroy']);
