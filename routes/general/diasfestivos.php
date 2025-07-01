<?php
use App\Http\Controllers\Api\general\DiasFestivosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/{year}', [DiasFestivosController::class, 'getHolidays']);