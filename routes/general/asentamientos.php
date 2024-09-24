<?php
use App\Http\Controllers\Api\general\AsentamientoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [AsentamientoController::class, 'index']);
Route::get('/{id}', [AsentamientoController::class, 'show']);
Route::post('/create', [AsentamientoController::class, 'store']);
Route::put('/asentamientos/{id}', [AsentamientoController::class, 'update']);
Route::patch('/asentamientos/{id}', [AsentamientoController::class, 'updatePartial']);
Route::delete('/asentamientos/{id}', [AsentamientoController::class, 'destroy']);
  