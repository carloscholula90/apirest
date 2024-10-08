<?php  
use App\Http\Controllers\Api\general\IntegraController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [IntegraController::class, 'index']);
Route::get('/{id}', [IntegraController::class, 'show']);
Route::post('/create', [IntegraController::class, 'store']);
Route::put('/{id}', [IntegraController::class, 'update']);
Route::patch('/{id}', [IntegraController::class, 'updatePartial']);
Route::delete('/{id}', [IntegraController::class, 'destroy']);
