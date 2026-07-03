<?php

use App\Http\Controllers\Api\escolar\HorarioProfesorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HorarioProfesorController::class, 'index']);
Route::get('/{uid}', [HorarioProfesorController::class, 'show']);
Route::post('/create', [HorarioProfesorController::class, 'store']);
Route::put('/{uid}/{secuencia}', [HorarioProfesorController::class, 'update']);
Route::delete('/{uid}/{secuencia}', [HorarioProfesorController::class, 'destroy']);
