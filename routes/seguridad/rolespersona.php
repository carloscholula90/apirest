<?php  
use App\Http\Controllers\Api\seguridad\RolesPersonaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [RolesPersonaController::class, 'index']);
Route::get('/{id}', [RolesPersonaController::class, 'show']);
Route::post('/create', [RolesPersonaController::class, 'store']);
Route::put('/{id}', [RolesPersonaController::class, 'updatePartial']);
Route::delete('/{id}', [RolesPersonaController::class, 'destroy']);
  