<?php
use App\Http\Controllers\Api\escolar\GrupoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/asignaturas/{grupo}', [GrupoController::class, 'obtenerAsignaturas']);
Route::get('/{idNivel}/{idPeriodo}/{idCarrera}', [GrupoController::class, 'show']);  
Route::get('/', [GrupoController::class, 'index']);
Route::post('/grupo/{gruposec}', [GrupoController::class, 'actualizarProfesor']);
Route::post('/actas/{gruposec}', [GrupoController::class, 'actualizarActas']);