<?php
use App\Http\Controllers\Api\tesoreria\EstadoCuentaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/validarQR/{uid}/{qr}', [EstadoCuentaController::class, 'validarQR']);
Route::get('/{uid}/{idPeriodo}/{matricula}/{tipoEdoCta}', [EstadoCuentaController::class, 'index']);
Route::get('/generaReporte/{uid}/{idPeriodo}/{matricula}/{tipoEdoCta}', [EstadoCuentaController::class, 'generaReporte']);
Route::get('/pagos/{uid}/{secuencia}', [EstadoCuentaController::class, 'getAbonos']);  
Route::post('/abonos', [EstadoCuentaController::class, 'guardarMovtos']);  
Route::post('/actualizaColegiatura', [EstadoCuentaController::class, 'actualizaColegiatura']);  
Route::post('/create', [EstadoCuentaController::class, 'store']);  
Route::get('/recibo', [EstadoCuentaController::class, 'recibo']);  
Route::get('/folios/{uid}/{tipoEdoCta}', [EstadoCuentaController::class, 'obtenerFolios']);  
Route::delete('/{uid}/{secuencia}/{consecutivo}/{uidcajero}', [EstadoCuentaController::class, 'destroy']);  
