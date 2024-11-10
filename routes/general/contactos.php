<?php
use App\Http\Controllers\Api\general\ContactoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [ContactoController::class, 'index']);
Route::get('/uid}/{idParentesco}/{idTipoContacto}/', [ContactoController::class, 'show']);
Route::post('/create', [ContactoController::class, 'store']);
//Route::put('/{idContacto}', [ContactoController::class, 'update']);
Route::delete('/{uid}/{idParentesco}/{idTipoContacto}/{consecutivo}', [ContactoController::class, 'destroy']);
