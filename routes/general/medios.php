<?php
use App\Http\Controllers\Api\general\MedioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [MedioController::class, 'index']);
Route::get('/{idMedio}', [MedioController::class, 'show']);
Route::post('/create', [MedioController::class, 'store']);  
Route::put('/{idMedio}', [MedioController::class, 'update']);
Route::delete('/{idMedio}', [MedioController::class, 'destroy']);
Route::post('/imprimeXls', [MedioController::class, 'exportaExcel']);  
  