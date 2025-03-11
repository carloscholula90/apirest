<?php
use App\Http\Controllers\Api\general\AlergiaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/{uid}', [AlergiaController::class, 'show']);
Route::post('/create', [AlergiaController::class, 'store']);
Route::put('/{uid}', [AlergiaController::class, 'update']);
Route::delete('/{uid}/{secuencia}', [AlergiaController::class, 'destroy']);
Route::post('/generaReporte', [AlergiaController::class, 'generaReporte']);
Route::post('/imprimeXls', [AlergiaController::class, 'exportaExcel']); 
