<?php  
use App\Http\Controllers\Api\seguridad\ModuloController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [ModuloController::class, 'index']);
Route::get('/{id}', [ModuloController::class, 'show']);
Route::post('/create', [ModuloController::class, 'store']);
Route::put('/{id}', [ModuloController::class, 'update']);
Route::delete('/{id}', [ModuloController::class, 'destroy']);
Route::post('/generaReporte', [ModuloController::class, 'generaReporte']);
Route::post('/imprimeXls', [ModuloController::class, 'exportaExcel']);  
