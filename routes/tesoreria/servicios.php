<?php
use App\Http\Controllers\Api\tesoreria\ServicioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/condonacion/{uid}/{matricula}/{tipoEdoCta}', [ServicioController::class, 'condonacion']);
Route::post('/condonacion', [ServicioController::class, 'condonar']);
Route::get('/{uid}/{secuencia}/{tipoEdoCta}', [ServicioController::class, 'index']);


