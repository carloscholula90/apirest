<?php
use App\Http\Controllers\Api\escolar\PlanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [PlanController::class, 'index']);
Route::post('/create', [PlanController::class, 'store']);
Route::put('/', [PlanController::class, 'update']);
Route::delete('/{idPlan}/{idCarrera}', [PlanController::class, 'destroy']);
Route::post('/generaReporte', [PlanController::class, 'generaReporte']);
Route::post('/imprimeXls', [PlanController::class, 'exportaExcel']); 


