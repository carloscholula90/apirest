<?php
use App\Http\Controllers\Api\escolar\EscolaridadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [EscolaridadController::class, 'index']);
Route::get('/{idEscolaridad}', [EscolaridadController::class, 'show']);
Route::post('/create', [EscolaridadController::class, 'store']);
Route::patch('/{idEscolaridad}', [EscolaridadController::class, 'updatePartial']);
Route::delete('/{idEscolaridad}', [EscolaridadController::class, 'destroy']);
  