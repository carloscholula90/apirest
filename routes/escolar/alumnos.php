<?php
use App\Http\Controllers\Api\escolar\AlumnoController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::get('/{uid}', [AlumnoController::class, 'getAlumno']);
  