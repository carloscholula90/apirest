<?php
use App\Http\Controllers\Api\tesoreria\NotaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [NotaController::class, 'index']);
Route::get('/{idNota}', [NotaController::class, 'show']);
Route::get('/servicios/{idNivel}', [NotaController::class, 'obtieneServicios']);
Route::post('/create', [NotaController::class, 'store']);
Route::put('/{idNota}', [NotaController::class, 'update']);
Route::delete('/{idNota}', [NotaController::class, 'destroy']);
Route::post('/generaReporte', [NotaController::class, 'generaReporte']);
Route::post('/imprimeXls', [NotaController::class, 'exportaExcel']); 
