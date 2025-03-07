<?php
use App\Http\Controllers\Api\tesoreria\ProductoServicioSATController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [ProductoServicioSATController::class, 'index']);
Route::get('/{idProductoServicio}', [ProductoServicioSATController::class, 'show']);
Route::post('/create', [ProductoServicioSATController::class, 'store']);
Route::put('/{idProductoServicio}', [ProductoServicioSATController::class, 'update']);
Route::delete('/{idProductoServicio}', [ProductoServicioSATController::class, 'destroy']);
Route::post('/generaReporte', [ProductoServicioSATController::class, 'generaReporte']);
Route::post('/imprimeXls', [ProductoServicioSATController::class, 'exportaExcel']); 
  