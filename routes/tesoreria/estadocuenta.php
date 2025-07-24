<?php
use App\Http\Controllers\Api\tesoreria\EstadoCuentaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/{uid}/{idPeriodo}/{matricula}', [EstadoCuentaController::class, 'index']);
Route::get('/generaReporte/{uid}/{idPeriodo}/{matricula}', [EstadoCuentaController::class, 'generaReporte']);
Route::post('/create', [EstadoCuentaController::class, 'store']);  
Route::get('/recibo', [EstadoCuentaController::class, 'recibo']);  

