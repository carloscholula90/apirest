<?php  
use App\Http\Controllers\Api\seguridad\PerfilAplicacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/aplicaciones', [PerfilAplicacionController::class, 'index']); 
Route::get('/', [PerfilAplicacionController::class, 'index']);    
Route::post('/create', [PerfilAplicacionController::class, 'store']);
Route::delete('/{idPerfil}/{idAplicacion}', [PerfilAplicacionController::class, 'destroy']);
