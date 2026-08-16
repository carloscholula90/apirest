<?php
use App\Http\Controllers\Api\tesoreria\CondonacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 



Route::get('/reporte/{fechaInicio}/{fechaFin}', [CondonacionController::class, 'reporteCondonaciones']);
Route::get('/reporteXls/{fechaInicio}/{fechaFin}', [CondonacionController::class, 'reporteCondonacionesExcel']);
Route::get('/{idFchInicio}/{idFechaFin}/{idCajero}', [CondonacionController::class, 'index']);
Route::get('/imprimeXls/{idFchInicio}/{idFechaFin}/{idCajero}', [CondonacionController::class, 'indexExcel']);
Route::post('/guardar', [CondonacionController::class, 'guardar']);
