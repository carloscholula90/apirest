<?php
use App\Http\Controllers\Api\general\CodigoPostalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/{idCodigoPostal}', [CodigoPostalController::class, 'show']);
