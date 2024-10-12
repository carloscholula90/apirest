<?php
use App\Http\Controllers\Api\general\aceptaAvisoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [aceptaAvisoController::class, 'index']);
/*Route::get('/{idAviso}', [aceptaAvisoController::class, 'show']);
Route::post('/create', [aceptaAvisoController::class, 'store']);  
Route::put('/{idAviso}', [aceptaAvisoController::class, 'update']);
Route::delete('/{idAviso}', [aceptaAvisoController::class, 'destroy']);*/
  