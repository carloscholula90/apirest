<?php  
use App\Http\Controllers\Api\seguridad\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 
   
Route::get('/', [UsuarioController::class, 'index']);
Route::get('/{id}/{pasw}', [UsuarioController::class, 'show']);  
Route::post('/create', [UsuarioController::class, 'store']);
Route::put('/', [UsuarioController::class, 'update']); 
Route::delete('/{uid}', [UsuarioController::class, 'destroy']);          
