<?php
use App\Http\Controllers\Api\escolar\KardexController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::get('/{id}/{idNivel}/{idCarrera}', [KardexController::class, 'generaReporte']);
  