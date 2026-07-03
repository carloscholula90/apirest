<?php
use App\Http\Controllers\Api\escolar\TurnoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [TurnoController::class, 'index']);
Route::get('/detalles', [TurnoController::class, 'turnosConDetalle']);
Route::get('/{idTurno}', [TurnoController::class, 'show']);
Route::post('/create', [TurnoController::class, 'store']);
Route::delete('/{idTurno}', [TurnoController::class, 'destroy']);
