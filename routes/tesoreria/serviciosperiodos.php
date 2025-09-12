<?php
use App\Http\Controllers\Api\tesoreria\ServiciosPeriodosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::post('/generaReporte', [ServiciosPeriodosController::class, 'generaReporte']);
Route::post('/imprimeXls', [ServiciosPeriodosController::class, 'exportaExcel']); 
Route::post('/create', [ServiciosPeriodosController::class, 'store']);
Route::get('/', [ServiciosPeriodosController::class, 'index']);
Route::put('/', [ServiciosPeriodosController::class, 'update']);
Route::delete('/{idNivel}/{idPeriodo}/{idServicio}', [ServiciosPeriodosController::class, 'destroy']);

