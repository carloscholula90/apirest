<?php  
use App\Http\Controllers\Api\seguridad\PermisoPersonaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/', [PermisoPersonaController::class, 'index']);
Route::post('/create', [PermisoPersonaController::class, 'store']);
Route::delete('/{uid}/{secuencia}', [PermisoPersonaController::class, 'destroy']);
Route::post('/generaReporte', [PermisoPersonaController::class, 'generaReporte']);
Route::post('/imprimeXls', [PermisoPersonaController::class, 'exportaExcel']);  
  
