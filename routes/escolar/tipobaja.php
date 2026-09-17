<?php

use App\Http\Controllers\Api\escolar\TipoBajaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TipoBajaController::class, 'index']);
