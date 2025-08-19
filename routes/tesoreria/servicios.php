<?php
use App\Http\Controllers\Api\tesoreria\ServicioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/{uid}/{secuencia}', [ServicioController::class, 'index']);

