<?php
use App\Http\Controllers\Api\tesoreria\CargosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/serviciosDisponibles/{idPeriodo}/{idNivel}', [CargosController::class, 'serviciosDisponibles']);
Route::get('/reportes/{concentrado}/{idPeriodo}/{idNivel}/{idServicio?}', [CargosController::class, 'index']);
Route::get('/imprimeXls/{concentrado}/{idPeriodo}/{idNivel}/{idServicio?}', [CargosController::class, 'indexExcel']);
Route::post('/actualizaCargos', [CargosController::class, 'actualizaCargos']);  
Route::post('/validaGeneraCargos', [CargosController::class, 'validaGeneraCargos']);  
