<?php
use App\Http\Controllers\Api\escolar\TipoExamenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [TipoExamenController::class, 'index']);
Route::get('/{idExamen}', [TipoExamenController::class, 'show']);
Route::post('/create', [TipoExamenController::class, 'store']);
Route::put('/{idExamen}', [TipoExamenController::class, 'update']);
Route::delete('/{idExamen}', [TipoExamenController::class, 'destroy']);
Route::post('/generaReporte', [TipoExamenController::class, 'generaReporte']);
Route::post('/imprimeXls', [TipoExamenController::class, 'exportaExcel']); 
