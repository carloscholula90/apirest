<?php
use App\Http\Controllers\Api\general\CiudadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [CiudadController::class, 'index']);
Route::get('/{iPais}/{idEstado}/{idCiudad}', [CiudadController::class, 'show']);
Route::post('/create', [CiudadController::class, 'store']);  
Route::put('/', [CiudadController::class, 'update']);
Route::delete('/{iPais}/{idEstado}/{idCiudad}', [CiudadController::class, 'destroy']);
  