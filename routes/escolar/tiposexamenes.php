<?php
use App\Http\Controllers\Api\escolar\TipoExamenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [TipoExamenController::class, 'index']);
Route::get('/{idTipoExamen}', [TipoExamenController::class, 'show']);
Route::post('/create', [TipoExamenController::class, 'store']);
Route::put('/{idTipoExamen}', [TipoExamenController::class, 'update']);
Route::delete('/{idTipoExamen}', [TipoExamenController::class, 'destroy']);
Route::post('/generaReporte', [TipoExamenController::class, 'generaReporte']);
Route::post('/imprimeXls', [TipoExamenController::class, 'exportaExcel']); 
