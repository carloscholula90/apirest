<?php
use App\Http\Controllers\Api\tesoreria\ServicioCarreraController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [ServicioCarreraController::class, 'index']);

