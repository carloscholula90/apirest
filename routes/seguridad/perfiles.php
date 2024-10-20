<?php  
use App\Http\Controllers\Api\seguridad\PerfilController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [PerfilController::class, 'index']);
Route::get('/{idPerfil}', [PerfilController::class, 'show']);
Route::post('/create', [PerfilController::class, 'store']);
Route::put('/{idPerfil}', [PerfilController::class, 'update']);
Route::delete('/{idPerfil}', [PerfilController::class, 'destroy']);
  