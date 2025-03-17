<?php
use App\Http\Controllers\Api\general\EdificioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [EdificioController::class, 'index']);
Route::get('/{idEdificio}', [EdificioController::class, 'show']);
Route::post('/create', [EdificioController::class, 'store']);
Route::put('/{idEdificio}', [EdificioController::class, 'update']);
Route::delete('/{idEdificio}', [EdificioController::class, 'destroy']);
Route::post('/generaReporte', [EdificioController::class, 'generaReporte']);
Route::post('/imprimeXls', [EdificioController::class, 'exportaExcel']); 
