<?php
use App\Http\Controllers\Api\escolar\TipoAsignaturaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [TipoAsignaturaController::class, 'index']);
Route::get('/{idTipoAsignatura}', [TipoAsignaturaController::class, 'show']);
Route::post('/create', [TipoAsignaturaController::class, 'store']);
Route::put('/{idTipoAsignatura}', [TipoAsignaturaController::class, 'update']);
Route::delete('/{idTipoAsignatura}', [TipoAsignaturaController::class, 'destroy']);
  