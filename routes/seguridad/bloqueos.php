<?php  
use App\Http\Controllers\Api\seguridad\BloqueoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::post('/generaReporte', [BloqueoController::class, 'generaReporte']);
Route::post('/imprimeXls', [BloqueoController::class, 'exportaExcel']);  
