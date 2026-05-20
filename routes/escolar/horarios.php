<?php
use App\Http\Controllers\Api\escolar\HorarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/{id}', [HorarioController::class, 'show']);
Route::post('/create', [HorarioController::class, 'store']);
Route::put('/{id}', [HorarioController::class, 'update']);
Route::delete('/{id}', [HorarioController::class, 'destroy']);