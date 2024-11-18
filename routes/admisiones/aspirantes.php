<?php
use App\Http\Controllers\Api\admisiones\AspiranteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [AspiranteController::class, 'index']);
Route::post('/create', [AspiranteController::class, 'store']);
//Route::get('/uid}/{idParentesco}/{idTipoAspirante}/', [AspiranteController::class, 'show']);
//Route::put('/{idAspirante}', [AspiranteController::class, 'update']);
//Route::delete('/{uid}/{idParentesco}/{idTipoAspirante}/{consecutivo}', [AspiranteController::class, 'destroy']);
