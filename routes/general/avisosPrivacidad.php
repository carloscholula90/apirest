<?php
use App\Http\Controllers\Api\general\avisosPrivacidadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [avisosPrivacidadController::class, 'index']);
Route::get('/active', [avisosPrivacidadController::class, 'active']);
Route::get('/{idAviso}', [avisosPrivacidadController::class, 'show']);
Route::post('/create', [avisosPrivacidadController::class, 'store']);  
Route::put('/{idAviso}', [avisosPrivacidadController::class, 'update']);
Route::delete('/{idAviso}', [avisosPrivacidadController::class, 'destroy']);
  