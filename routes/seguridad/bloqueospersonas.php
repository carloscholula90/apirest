<?php  
use App\Http\Controllers\Api\seguridad\BloqueoPersonaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::post('/generaReporte', [BloqueoPersonaController::class, 'generaReporte']);
Route::post('/imprimeXls', [BloqueoPersonaController::class, 'exportaExcel']);  
