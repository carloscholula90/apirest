<?php
use App\Http\Controllers\Api\escolar\ActaController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::get('/', [ActaController::class, 'generaReporte']);
  