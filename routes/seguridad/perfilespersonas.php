<?php  
use App\Http\Controllers\Api\seguridad\PerfilesPersonaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [PerfilesPersonaController::class, 'index']);
Route::post('/', [PerfilesPersonaController::class, 'update']);
Route::delete('/{id}', [PerfilesPersonaController::class, 'destroy']);
  