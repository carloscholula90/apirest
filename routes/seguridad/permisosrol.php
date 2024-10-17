<?php  
use App\Http\Controllers\Api\seguridad\PermisoRolController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [PermisoRolController::class, 'index']);
Route::get('/{idApp}/{idRol}', [PermisoRolController::class, 'show']);
Route::post('/create', [PermisoRolController::class, 'store']);
Route::put('/{id}', [PermisoRolController::class, 'update']);
Route::delete('/{id}', [PermisoRolController::class, 'destroy']);
  