<?php
use App\Http\Controllers\Api\escolar\DetasignaturaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [DetasignaturaController::class, 'index']);
Route::post('/create', [DetasignaturaController::class, 'store']);
Route::put('/', [DetasignaturaController::class, 'update']);
Route::delete('/{secPlanes}', [DetasignaturaController::class, 'destroy']);


