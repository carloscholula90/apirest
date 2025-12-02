<?php
use App\Http\Controllers\Api\tesoreria\CargosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/reportes/{concentrado}/{idPeriodo}/{idNivel}/{idCarrera}', [CargosController::class, 'index']);   
