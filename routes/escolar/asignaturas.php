<?php
use App\Http\Controllers\Api\escolar\AsignaturaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [AsignaturaController::class, 'index']);
Route::get('/{idAsignatura}', [AsignaturaController::class, 'show']);
Route::post('/create', [AsignaturaController::class, 'store']);
Route::put('/{idAsignatura}', [AsignaturaController::class, 'update']);
Route::delete('/{idAsignatura}', [AsignaturaController::class, 'destroy']);
Route::post('/generaReporte', [AsignaturaController::class, 'generaReporte']);
Route::post('/imprimeXls', [AsignaturaController::class, 'exportaExcel']); 
