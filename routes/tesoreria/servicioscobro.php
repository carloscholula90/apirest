<?php
use App\Http\Controllers\Api\tesoreria\ServiciosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::post('/generaReporte', [ServiciosController::class, 'generaReporte']);
Route::post('/imprimeXls', [ServiciosController::class, 'exportaExcel']); 
Route::post('/create', [ServiciosController::class, 'store']);
Route::get('/', [ServiciosController::class, 'index']);
Route::put('/', [ServiciosController::class, 'update']);
Route::delete('/{idServicio}', [ServiciosController::class, 'destroy']);

