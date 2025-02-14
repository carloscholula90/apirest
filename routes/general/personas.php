<?php  
use App\Http\Controllers\Api\general\PersonaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::post('/generaReporte', [PersonaController::class, 'generaReportePersonas']);
Route::get('/buscar/{var}', [PersonaController::class, 'getPersonasLike']);
Route::get('/contacto/{id}', [PersonaController::class, 'recovery']);
Route::get('/{id}', [PersonaController::class, 'show']);
Route::get('/', [PersonaController::class, 'index']);
Route::post('/create', [PersonaController::class, 'store']);
Route::put('/{id}', [PersonaController::class, 'update']);
Route::delete('/{id}', [PersonaController::class, 'destroy']);   
  
