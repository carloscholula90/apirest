<?php  
use App\Http\Controllers\Api\general\PersonaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [PersonaController::class, 'index']);
Route::get('/{id}', [PersonaController::class, 'show']);   
Route::post('/create', [PersonaController::class, 'store']);
Route::put('/{id}', [PersonaController::class, 'update']);
Route::delete('/{id}', [PersonaController::class, 'destroy']);
  
