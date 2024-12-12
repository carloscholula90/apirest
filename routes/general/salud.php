<?php
use App\Http\Controllers\Api\general\SaludController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [SaludController::class, 'index']);
Route::get('/{uid}', [SaludController::class, 'show']);
Route::post('/create', [SaludController::class, 'store']);
Route::delete('/{uid}/{secuencia}', [SaludController::class, 'destroy']);
