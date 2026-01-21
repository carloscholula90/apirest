<?php
use App\Http\Controllers\Api\tesoreria\CargosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/reportes/{concentrado}/{idPeriodo}/{idNivel}', [CargosController::class, 'index']);  
Route::get('/imprimeXls/{concentrado}/{idPeriodo}/{idNivel}', [CargosController::class, 'indexExcel']);  
Route::post('/actualizaCargos', [CargosController::class, 'actualizaCargos']);  