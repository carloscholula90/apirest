<?php
use App\Http\Controllers\Api\tesoreria\IngresosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/reportes/{concentrado}/{idFchInicio}/{idFechaFin}/{idCajero?}/{idCarrera?}', [IngresosController::class, 'index']);   
Route::get('/imprimeXls/{concentrado}/{idFchInicio}/{idFechaFin}/{idCajero?}/{idCarrera?}', [IngresosController::class, 'indexExcel']);   
