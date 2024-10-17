<?php  
use App\Http\Controllers\Api\seguridad\PermisosRolController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [PermisosRolController::class, 'index']);
Route::get('/{id}', [PermisosRolController::class, 'show']);
Route::post('/create', [PermisosRolController::class, 'store']);
Route::put('/{id}', [PermisosRolController::class, 'update']);
Route::delete('/{id}', [PermisosRolController::class, 'destroy']);
  