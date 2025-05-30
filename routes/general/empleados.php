<?php
use App\Http\Controllers\Api\general\EmpleadoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [EmpleadoController::class, 'index']);
Route::post('/create', [EmpleadoController::class, 'store']);  
Route::put('/', [EmpleadoController::class, 'update']);
Route::delete('/{uid}', [EmpleadoController::class, 'destroy']);
      