<?php
use App\Http\Controllers\Api\escolar\OfertaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::post('/', [OfertaController::class, 'store']);   
