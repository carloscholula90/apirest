<?php
use App\Http\Controllers\Api\admisiones\AspiranteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [AspiranteController::class, 'index']);
Route::get('/listado', [AspiranteController::class, 'index2']);
Route::get('/{uid}', [AspiranteController::class, 'generaReporte']);
Route::post('/alumno', [AspiranteController::class, 'convierte']);
Route::post('/create', [AspiranteController::class, 'store']);
Route::delete('/{uid}/{secuencia}', [AspiranteController::class, 'destroy']);
