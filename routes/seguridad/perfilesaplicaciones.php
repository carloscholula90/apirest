<?php  
use App\Http\Controllers\Api\seguridad\PerfilAplicacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [PerfilAplicacionController::class, 'index']);
Route::get('/aplicaciones', [PerfilAplicacionController::class, 'index2']);     
Route::post('/create', [PerfilAplicacionController::class, 'store']);
Route::put('/{idPerfil}', [PerfilAplicacionController::class, 'update']);
Route::delete('/{idPerfil}/{idAplicacion}', [PerfilAplicacionController::class, 'destroy']);
