<?php
use App\Http\Controllers\Api\escolar\AlumnoController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::get('/avance/{uid}/{secuencia}', [AlumnoController::class, 'getAvance']);  
Route::get('/{uid}', [AlumnoController::class, 'getAlumno']);

  