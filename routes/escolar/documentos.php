<?php
use App\Http\Controllers\Api\escolar\DocumentoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [DocumentoController::class, 'index']);
Route::get('/{idDocumento}', [DocumentoController::class, 'show']);
Route::post('/create', [DocumentoController::class, 'store']);
Route::put('/{idDocumento}', [DocumentoController::class, 'update']);
Route::delete('/{idDocumento}', [DocumentoController::class, 'destroy']);
