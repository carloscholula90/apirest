<?php
use App\Http\Controllers\Api\escolar\DocumentosController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::get('/paseslista', [DocumentosController::class, 'generatePaseLista']);
Route::get('/actas', [DocumentosController::class, 'generaActa']);
  