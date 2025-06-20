<?php
use App\Http\Controllers\Api\escolar\InscripcionesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::get('/', [InscripcionesController::class, 'index']);