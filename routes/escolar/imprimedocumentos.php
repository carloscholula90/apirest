<?php
use App\Http\Controllers\Api\escolar\DocumentosController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::get('/paseslista', [DocumentosController::class, 'generatePaseLista']);
Route::get('/actas', [DocumentosController::class, 'generaActa']);
Route::get('/adeudos', [DocumentosController::class, 'generaAdeudoDoctos']);
Route::get('/cuadroincripcion', [DocumentosController::class, 'cuadroIncripcion']);
Route::get('/soldesfase', [DocumentosController::class, 'solicituDesfase']);
Route::get('/circular', [DocumentosController::class, 'circularEstudiantil']);




  