<?php
use App\Http\Controllers\Api\escolar\GrupoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [GrupoController::class, 'index']);
Route::get('/{idNivel}/{idPeriodo}/{idCarrera}', [GrupoController::class, 'show']);
  