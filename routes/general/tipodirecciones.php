<?php
use App\Http\Controllers\Api\general\TipoDireccionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [TipoDireccionController::class, 'index']);
Route::get('/{idTipoDireccion}', [TipoDireccionController::class, 'show']);
Route::post('/create', [TipoDireccionController::class, 'store']);
Route::put('/{idTipoDireccion}', [TipoDireccionController::class, 'update']);
Route::delete('/{idTipoDireccion}', [TipoDireccionController::class, 'destroy']);
