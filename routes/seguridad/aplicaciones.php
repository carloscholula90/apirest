<?php  
use App\Http\Controllers\Api\seguridad\AplicacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::post('/generaReporte', [AplicacionController::class, 'generaReporte']);
Route::post('/imprimeXls', [AplicacionController::class, 'exportaExcel']); 
Route::get('/', [AplicacionController::class, 'index']);
Route::get('/{id}', [AplicacionController::class, 'show']);
Route::post('/create', [AplicacionController::class, 'store']);
Route::put('/{id}', [AplicacionController::class, 'update']);
Route::patch('/{id}', [AplicacionController::class, 'updatePartial']);
Route::delete('/{id}', [AplicacionController::class, 'destroy']);
  