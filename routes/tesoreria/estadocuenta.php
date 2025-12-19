<?php
use App\Http\Controllers\Api\tesoreria\EstadoCuentaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::get('/{uid}/{idPeriodo}/{matricula}/{tipoEdoCta}', [EstadoCuentaController::class, 'index']);
Route::get('/generaReporte/{uid}/{idPeriodo}/{matricula}/{tipoEdoCta}', [EstadoCuentaController::class, 'generaReporte']);
Route::post('/abonos', [EstadoCuentaController::class, 'guardarMovtos']);  
Route::post('/create', [EstadoCuentaController::class, 'store']);  
Route::get('/recibo', [EstadoCuentaController::class, 'recibo']);  
Route::get('/folios/{uid}/{tipoEdoCta}', [EstadoCuentaController::class, 'obtenerFolios']);  

