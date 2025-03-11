<?php
use App\Http\Controllers\Api\general\CodigoPostalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [CodigoPostalController::class, 'index']);
Route::get('/{idCodigoPostal}', [CodigoPostalController::class, 'show']);
Route::post('/create', [CodigoPostalController::class, 'store']);
Route::put('/{idCodigoPostal}', [CodigoPostalController::class, 'update']);
Route::delete('/{idCodigoPostal}', [CodigoPostalController::class, 'destroy']);
Route::post('/generaReporte', [CodigoPostalController::class, 'generaReporte']);
Route::post('/imprimeXls', [CodigoPostalController::class, 'exportaExcel']); 
