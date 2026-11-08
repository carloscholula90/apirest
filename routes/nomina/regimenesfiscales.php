<?php

use App\Http\Controllers\Api\nomina\RegimenFiscalNominaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RegimenFiscalNominaController::class, 'index']);
Route::get('/sinregimen', [RegimenFiscalNominaController::class, 'empleadosSinRegimen']);
Route::post('/create', [RegimenFiscalNominaController::class, 'store']);
Route::get('/{idRegimenFiscal}/costos', [RegimenFiscalNominaController::class, 'costos']);
Route::post('/{idRegimenFiscal}/costos', [RegimenFiscalNominaController::class, 'storeCosto']);
Route::get('/{idRegimenFiscal}/impuestos', [RegimenFiscalNominaController::class, 'impuestos']);
Route::post('/{idRegimenFiscal}/impuestos', [RegimenFiscalNominaController::class, 'storeImpuesto']);
Route::delete('/{idRegimenFiscal}/impuestos/{idImpuesto}', [RegimenFiscalNominaController::class, 'destroyImpuesto']);
Route::put('/{idRegimenFiscal}/empleados/{uid}', [RegimenFiscalNominaController::class, 'asignarEmpleado']);
