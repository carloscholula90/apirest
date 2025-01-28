<?php
use App\Http\Controllers\Api\escolar\PaseListaController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::get('/', [PaseListaController::class, 'generaReporte']);
  