<?php
use App\Http\Controllers\Api\general\AlergiasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [AlergiasController::class, 'index']);
Route::get('/{idAlergia}', [AlergiasController::class, 'show']);
Route::post('/create', [AlergiasController::class, 'store']);
Route::put('/{idAlergia}', [AlergiasController::class, 'update']);
Route::delete('/{idAlergia}', [AlergiasController::class, 'destroy']);
