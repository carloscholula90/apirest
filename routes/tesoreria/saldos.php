<?php
use App\Http\Controllers\Api\tesoreria\SaldosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::get('/generaReporte/{idNivel}', [SaldosController::class, 'generaReporte']);   
Route::get('/imprimeXls/{idNivel}', [SaldosController::class, 'exportaExcel']);

