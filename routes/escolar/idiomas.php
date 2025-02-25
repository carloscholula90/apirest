<?php
use App\Http\Controllers\Api\escolar\IdiomasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::post('/generaReporte', [IdiomasController::class, 'generaReporte']);   
Route::post('/imprimeXls', [IdiomasController::class, 'exportaExcel']);
Route::get('/', [IdiomasController::class, 'index']);
Route::get('/{idIdioma}', [IdiomasController::class, 'show']);
Route::post('/create', [IdiomasController::class, 'store']);
Route::put('/{idIdioma}', [IdiomasController::class, 'updatePartial']);
Route::delete('/{idIdioma}', [IdiomasController::class, 'destroy']);
  