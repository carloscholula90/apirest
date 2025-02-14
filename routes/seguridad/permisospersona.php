<?php  
use App\Http\Controllers\Api\seguridad\PermisoPersonaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [PermisoPersonaController::class, 'index']);
Route::get('/{id}/{idRol}', [PermisoPersonaController::class, 'show']);
//Route::post('/create', [PermisoPersonaController::class, 'store']);
//Route::put('/{id}', [PermisoPersonaController::class, 'update']);
//Route::delete('/{id}', [PermisoPersonaController::class, 'destroy']);
  
