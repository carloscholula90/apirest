<?php
use App\Http\Controllers\Api\general\TipoContactoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [TipoContactoController::class, 'index']);
Route::get('/{idTipoContacto}', [TipoContactoController::class, 'show']);
Route::post('/create', [TipoContactoController::class, 'store']);  
Route::put('/{idTipoContacto}', [TipoContactoController::class, 'update']);
Route::delete('/{idTipoContacto}', [TipoContactoController::class, 'destroy']);
Route::post('/imprimeXls', [TipoContactoController::class, 'exportaExcel']); 
  