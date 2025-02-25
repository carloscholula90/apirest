<?php
use App\Http\Controllers\Api\general\EdoCivilController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 



Route::post('/generaReporte', [EdoCivilController::class, 'generaReporte']);   
Route::post('/imprimeXls', [EdoCivilController::class, 'exportaExcel']);  
Route::get('/', [EdoCivilController::class, 'index']);
Route::get('/{idEdoCivil}', [EdoCivilController::class, 'show']);
Route::post('/create', [EdoCivilController::class, 'store']);        
Route::put('/{idEdoCivil}', [EdoCivilController::class, 'update']);
Route::delete('/{idEdoCivil}', [EdoCivilController::class, 'destroy']);
  