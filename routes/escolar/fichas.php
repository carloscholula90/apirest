<?php
use App\Http\Controllers\Api\escolar\FichasController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::get('/{idPeriodo}/{idNivel}/{idCarrera}', [FichasController::class, 'generarYGuardarPDF']);
  