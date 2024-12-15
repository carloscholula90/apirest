<?php
use App\Http\Controllers\Api\escolar\PeriodoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [PeriodoController::class, 'index']);
Route::get('/{idPeriodo}/{idNivel}', [PeriodoController::class, 'show']);
Route::post('/create', [PeriodoController::class, 'store']);
Route::put('/{idPeriodo}/{idNivel}', [PeriodoController::class, 'update']);
Route::patch('/{idPeriodo}/{idNivel}', [PeriodoController::class, 'updatePartial']);
Route::delete('/{idPeriodo}/{idNivel}', [PeriodoController::class, 'destroy']);