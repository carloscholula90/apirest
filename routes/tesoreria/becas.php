<?php
use App\Http\Controllers\Api\tesoreria\BecasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::post('/generaReporte', [BecasController::class, 'generaReporte']);   
Route::post('/imprimeXls', [BecasController::class, 'exportaExcel']);
Route::get('/', [BecasController::class, 'index']);
Route::post('/create', [BecasController::class, 'store']);
Route::put('/{idBeca}', [BecasController::class, 'update']);
Route::delete('/{idBeca}', [BecasController::class, 'destroy']);
