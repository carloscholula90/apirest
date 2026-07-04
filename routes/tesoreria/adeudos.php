<?php
use App\Http\Controllers\Api\tesoreria\AdeudosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::get('/generaReporte/{idNivel}/{idPeriodo}/{fechaLimite}', [AdeudosController::class, 'generaReporte']);   
Route::get('/imprimeXls/{idNivel}/{idPeriodo}/{fechaLimite}', [AdeudosController::class, 'exportaExcel']);
