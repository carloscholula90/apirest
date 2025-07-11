<?php
use App\Http\Controllers\Api\escolar\CancelaInscripcionesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::get('/{idNivel}', [CancelaInscripcionesController::class, 'index']);
Route::post('/create', [CancelaInscripcionesController::class, 'store']);