<?php
use App\Http\Controllers\Api\general\AceptaAvisoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [AceptaAvisoController::class, 'index']);
Route::get('/active', [AceptaAvisoController::class, 'active']);
Route::post('/create', [AceptaAvisoController::class, 'store']);
Route::delete('/', [aceptaAvisoController::class, 'destroy']);  