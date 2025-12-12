<?php
use App\Http\Controllers\Api\tesoreria\ServicioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/condonacion/{uid}/{secuencia}/{tipoEdoCta}', [ServicioController::class, 'condonacion']);
Route::get('/{uid}/{secuencia}/{tipoEdoCta}', [ServicioController::class, 'index']);


