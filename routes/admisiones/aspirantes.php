<?php
use App\Http\Controllers\Api\admisiones\AspiranteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [AspiranteController::class, 'index']);
Route::post('/create', [AspiranteController::class, 'store']);
