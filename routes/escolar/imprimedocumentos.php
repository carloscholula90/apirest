<?php
use App\Http\Controllers\Api\escolar\DocumentosController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::post('/paseslista', [DocumentosController::class, 'generatePaseLista']);
Route::get('/actas', [DocumentosController::class, 'generaActa']);
Route::get('/adeudos', [DocumentosController::class, 'generaAdeudoDoctos']);
Route::get('/cuadroincripcion', [DocumentosController::class, 'cuadroIncripcion']);
Route::get('/soldesfase/{nombre}/{matricula}/{uid}', [DocumentosController::class, 'solicituDesfase']);

Route::get('/autorizacionImagen/{uid}/{nombre}/{programa}/{tipo}', [DocumentosController::class, 'autorizacionImagen']);
//Route::get('/circular', [DocumentosController::class, 'circularEstudiantil']);




  