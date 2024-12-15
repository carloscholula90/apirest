<?php
use App\Http\Controllers\Api\tesoreria\FormaPagoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [FormaPagoController::class, 'index']);
Route::get('/{idFormaPago}', [FormaPagoController::class, 'show']);
Route::post('/create', [FormaPagoController::class, 'store']);
Route::put('/{idFormaPago}', [FormaPagoController::class, 'update']);
Route::delete('/{idFormaPago}', [FormaPagoController::class, 'destroy']);
