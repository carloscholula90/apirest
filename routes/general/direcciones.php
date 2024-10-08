<?php
use App\Http\Controllers\Api\general\DireccionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [DireccionController::class, 'index']);
Route::get('/{uid}/{idParentesco}/', [DireccionController::class, 'show']);
Route::post('/create', [DireccionController::class, 'store']);  
Route::put('/', [DireccionController::class, 'update']);
Route::delete('/{uid}/{consecutivo}', [DireccionController::class, 'destroy']);
      