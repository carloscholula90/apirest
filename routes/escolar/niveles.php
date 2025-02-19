<?php
use App\Http\Controllers\Api\escolar\NivelController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::post('/generaReporte', [NivelController::class, 'generaReporte']);
Route::post('/imprimeXls', [NivelController::class, 'exportaExcel']); 
Route::get('/', [NivelController::class, 'index']);
Route::get('/{idNivel}', [NivelController::class, 'show']);
Route::post('/create', [NivelController::class, 'store']);
Route::put('/{idNivel}', [NivelController::class, 'update']);
Route::patch('/{idNivel}', [NivelController::class, 'updatePartial']);
Route::delete('/{idNivel}', [NivelController::class, 'destroy']); 