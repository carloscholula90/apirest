<?php
use App\Http\Controllers\Api\tesoreria\CondonacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 



Route::get('/{idFchInicio}/{idFechaFin}/{idCajero}', [CondonacionController::class, 'index']);
Route::get('/imprimeXls/{idFchInicio}/{idFechaFin}/{idCajero}', [CondonacionController::class, 'indexExcel']);