<?php
use App\Http\Controllers\Api\escolar\PeriodoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::post('/imprimeXls', [PeriodoController::class, 'exportaExcel']);   
Route::post('/generaReporte', [PeriodoController::class, 'generaReporte']);  
Route::get('/activos', [PeriodoController::class, 'obtenerActivos']);
Route::get('/', [PeriodoController::class, 'index']);
Route::get('/{idPeriodo}/{idNivel}', [PeriodoController::class, 'show']);
Route::get('/{idNivel}', [PeriodoController::class, 'showNivel']);
Route::post('/create', [PeriodoController::class, 'store']);
Route::put('/{idPeriodo}/{idNivel}', [PeriodoController::class, 'update']);
Route::patch('/{idPeriodo}/{idNivel}', [PeriodoController::class, 'updatePartial']);
Route::delete('/{idPeriodo}/{idNivel}', [PeriodoController::class, 'destroy']);

