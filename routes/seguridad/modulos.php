<?php  
use App\Http\Controllers\Api\seguridad\ModuloController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [ModuloController::class, 'index']);
Route::get('/{id}', [ModuloController::class, 'show']);
Route::post('/create', [ModuloController::class, 'store']);
Route::put('/modulos/{id}', [ModuloController::class, 'update']);
Route::patch('/modulos/{id}', [ModuloController::class, 'updatePartial']);
Route::delete('/modulos/{id}', [ModuloController::class, 'destroy']);
  