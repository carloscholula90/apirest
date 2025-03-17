<?php
use App\Http\Controllers\Api\tesoreria\UsoCFDIController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [UsoCFDIController::class, 'index']);
Route::get('/{idUsoCFDI}', [UsoCFDIController::class, 'show']);
Route::post('/create', [UsoCFDIController::class, 'store']);
Route::put('/{idUsoCFDI}', [UsoCFDIController::class, 'update']);
Route::delete('/{idUsoCFDI}', [UsoCFDIController::class, 'destroy']);
Route::post('/generaReporte', [UsoCFDIController::class, 'generaReporte']);
Route::post('/imprimeXls', [UsoCFDIController::class, 'exportaExcel']); 
