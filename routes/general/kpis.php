<?php
use App\Http\Controllers\Api\general\KpisController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::get('/', [KpisController::class, 'getKpis']);