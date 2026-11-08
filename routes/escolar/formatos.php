<?php
use App\Http\Controllers\Api\escolar\FormatoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FormatoController::class, 'index']);
