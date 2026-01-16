<?php
use App\Http\Controllers\Api\tesoreria\CierreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 
 
Route::post('/generaCierre', [CierreController::class, 'generaCierre']);  