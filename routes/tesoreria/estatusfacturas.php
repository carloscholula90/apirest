<?php
use App\Http\Controllers\Api\tesoreria\EstatusFacturaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [EstatusFacturaController::class, 'index']);
Route::get('/{idEstatusFactura}', [EstatusFacturaController::class, 'show']);
Route::post('/create', [EstatusFacturaController::class, 'store']);
Route::put('/{idEstatusFactura}', [EstatusFacturaController::class, 'update']);
Route::delete('/{idEstatusFactura}', [EstatusFacturaController::class, 'destroy']);
Route::post('/generaReporte', [EstatusFacturaController::class, 'generaReporte']);
Route::post('/imprimeXls', [EstatusFacturaController::class, 'exportaExcel']); 
