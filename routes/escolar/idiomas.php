<?php
use App\Http\Controllers\Api\escolar\IdiomasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [IdiomasController::class, 'index']);
Route::get('/{idIdioma}', [IdiomasController::class, 'show']);
Route::post('/create', [IdiomasController::class, 'store']);
Route::put('/{idIdioma}', [IdiomasController::class, 'updatePartial']);
Route::delete('/{idIdioma}', [IdiomasController::class, 'destroy']);
  