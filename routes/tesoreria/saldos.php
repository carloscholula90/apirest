<?php
use App\Http\Controllers\Api\tesoreria\SaldosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::get('/generaReporte/{idNivel}/{idPeriodo}/{fechaLimite}', [SaldosController::class, 'generaReporte']);   
Route::get('/imprimeXls/{idNivel}/{idPeriodo}/{fechaLimite}', [SaldosController::class, 'exportaExcel']);
Route::get('/saldosnegativos/{idNivel}/{idPeriodo}', [SaldosController::class, 'saldosNegativos'])
    ->whereNumber('idNivel')
    ->whereNumber('idPeriodo');

Route::get('/saldosnegativosXls/{idNivel}/{idPeriodo}', [SaldosController::class, 'exportaExcelSaldosNegativos'])
    ->whereNumber('idNivel')
    ->whereNumber('idPeriodo');

  
