<?php
use App\Http\Controllers\Api\tesoreria\ServicioCarreraController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [ServicioCarreraController::class, 'index']);
Route::post('/create', [ServicioCarreraController::class, 'store']);
Route::put('/{idProductoServicio}', [ServicioCarreraController::class, 'update']);
Route::delete('/{idNivel}/{idPeriodo}/{idCarrera}/{idServicio}/{idTurno}', [ServicioCarreraController::class, 'destroy']);

