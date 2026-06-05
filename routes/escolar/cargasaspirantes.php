<?php
use App\Http\Controllers\Api\admisiones\CargaAspController;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Route; 


Route::post('/', [CargaAspController::class, 'store']);   