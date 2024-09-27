<?php
use App\Http\Controllers\Api\escolar\CarreraController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [CarreraController::class, 'index']);
Route::get('/{idCarrera}', [CarreraController::class, 'show']);
Route::post('/create', [CarreraController::class, 'store']);
Route::put('/{idCarrera}', [CarreraController::class, 'update']);
Route::patch('/{idCarrera}', [CarreraController::class, 'updatePartial']);
Route::delete('/{idCarrera}', [CarreraController::class, 'destroy']);
  