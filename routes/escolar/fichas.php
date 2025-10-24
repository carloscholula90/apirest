<?php
use App\Http\Controllers\Api\escolar\FichasController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::get('/{idPeriodo}/{idCarrera}/{parcialidad}', [FichasController::class, 'generarYGuardarPDF']);
  