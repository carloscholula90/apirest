<?php
use App\Http\Controllers\Api\tesoreria\ServicioCarreraController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::post('/generaReporte', [ServicioCarreraController::class, 'generaReporte']);
Route::post('/imprimeXls', [ServicioCarreraController::class, 'exportaExcel']); 
Route::post('/create', [ServicioCarreraController::class, 'store']);
Route::get('/', [ServicioCarreraController::class, 'index']);
Route::put('/', [ServicioCarreraController::class, 'update']);
Route::delete('/{idNivel}/{idPeriodo}/{idServicio}', [ServicioCarreraController::class, 'destroy']);

