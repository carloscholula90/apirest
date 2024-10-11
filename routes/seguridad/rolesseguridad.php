<?php  
use App\Http\Controllers\Api\seguridad\RolSegController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [RolSegController::class, 'index']);
Route::get('/{id}', [RolSegController::class, 'show']);
Route::post('/create', [RolSegController::class, 'store']);
Route::put('/{id}', [RolSegController::class, 'updatePartial']);
Route::delete('/{id}', [RolSegController::class, 'destroy']);
  