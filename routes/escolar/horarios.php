<?php
use App\Http\Controllers\Api\escolar\HorarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/vista-previa', [HorarioController::class, 'vistaPrevia']);
Route::get('/{grupoSec}', [HorarioController::class, 'show']);
Route::post('/create', [HorarioController::class, 'store']);
Route::put('/{grupoSec}', [HorarioController::class, 'update']);
Route::delete('/{grupoSec}', [HorarioController::class, 'destroy']);
