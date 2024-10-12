<?php
use App\Http\Controllers\Api\general\PaisController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [PaisController::class, 'index']);
Route::get('/nacionalidad', [PaisController::class, 'nacionalidad']);
Route::get('/nacionalidad/{idPais}', [PaisController::class, 'buscaNacionalidad']);
Route::get('/{idPais}', [PaisController::class, 'show']);
Route::post('/create', [PaisController::class, 'store']);  
Route::patch('/{idPais}', [PaisController::class, 'updatePartial']);
Route::delete('/{idPais}', [PaisController::class, 'destroy']);
