<?php  
use App\Http\Controllers\Api\seguridad\PerfilAplicacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [PerfilAplicacionController::class, 'index']);
Route::get('/{idPerfil}', [PerfilAplicacionController::class, 'show']);
Route::post('/create', [PerfilAplicacionController::class, 'store']);
Route::put('/{idPerfil}', [PerfilAplicacionController::class, 'update']);
Route::delete('/{idPerfil}', [PerfilAplicacionController::class, 'destroy']);
