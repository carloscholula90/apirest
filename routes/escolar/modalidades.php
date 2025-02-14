<?php
use App\Http\Controllers\Api\escolar\ModalidadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [ModalidadController::class, 'index']);

Route::post('/generaReporte', [ModalidadController::class, 'generaReporte']);
Route::get('/{id}', [ModalidadController::class, 'show']);
Route::post('/create', [ModalidadController::class, 'store']);
Route::put('/{id}', [ModalidadController::class, 'update']);
Route::delete('/{id}', [ModalidadController::class, 'destroy']);
Route::post('/imprimeXls', [ModalidadController::class, 'exportaExcel']);   
  