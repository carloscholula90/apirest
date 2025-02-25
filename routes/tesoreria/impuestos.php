<?php
use App\Http\Controllers\Api\tesoreria\ImpuestoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::post('/generaReporte', [ImpuestoController::class, 'generaReporte']);   
Route::post('/imprimeXls', [ImpuestoController::class, 'exportaExcel']);
Route::get('/', [ImpuestoController::class, 'index']);
Route::get('/{idImpuesto}', [ImpuestoController::class, 'show']);
Route::post('/create', [ImpuestoController::class, 'store']);
Route::put('/{idImpuesto}', [ImpuestoController::class, 'update']);
Route::delete('/{idImpuesto}', [ImpuestoController::class, 'destroy']);
