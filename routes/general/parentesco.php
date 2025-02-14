<?php
use App\Http\Controllers\Api\general\ParentescoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::post('/generaReporte', [ParentescoController::class, 'generaReporte']);
Route::get('/', [ParentescoController::class, 'index']);
Route::get('/{idParentesco}', [ParentescoController::class, 'show']);
Route::post('/create', [ParentescoController::class, 'store']);  
Route::put('/{idParentesco}', [ParentescoController::class, 'update']);
Route::delete('/{idParentesco}', [ParentescoController::class, 'destroy']);
Route::post('/imprimeXls', [ParentescoController::class, 'exportaExcel']);  
  