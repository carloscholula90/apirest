<?php
use App\Http\Controllers\Api\escolar\ReciboController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::get('/{uid}/{recibo}', [ReciboController::class, 'generarYGuardarPDF']);

  