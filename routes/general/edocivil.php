<?php
use App\Http\Controllers\Api\general\MedioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [MedioController::class, 'index']);
Route::get('/{idEdoCivil}', [MedioController::class, 'show']);
Route::post('/create', [MedioController::class, 'store']);  
Route::put('/{idEdoCivil}', [MedioController::class, 'update']);
Route::delete('/{idEdoCivil}', [MedioController::class, 'destroy']);
  