<?php  
use App\Http\Controllers\Api\general\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [UsuarioController::class, 'index']);
Route::get('/{id}/{pasw}', [UsuarioController::class, 'show']);
Route::post('/create', [UsuarioController::class, 'store']);
Route::put('/{id}', [UsuarioController::class, 'update']);
Route::patch('/{id}', [UsuarioController::class, 'updatePartial']);
Route::delete('/{id}', [UsuarioController::class, 'destroy']);
/*Route::get('/{id}/{slug?}', [UsuarioController::class, 'show']); parametros opcionales*/
  