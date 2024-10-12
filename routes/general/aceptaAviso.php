<?php
use App\Http\Controllers\Api\general\AceptaAvisoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [AceptaAvisoController::class, 'index']);
Route::get('/active', [AceptaAvisoController::class, 'active']);
Route::post('/create', [AceptaAvisoController::class, 'store']);

/*Route::get('/{idAviso}', [aceptaAvisoController::class, 'show']);
Route::put('/{idAviso}/{uid}', [aceptaAvisoController::class, 'update']);
Route::delete('/{idAviso}', [aceptaAvisoController::class, 'destroy']);*/
  