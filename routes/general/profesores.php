<?php  
use App\Http\Controllers\Api\general\ProfesorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/nuevo/{var}', [ProfesorController::class, 'getPersonas']);
Route::get('/{grupoSec}', [ProfesorController::class, 'getPersonasLike']);

  
