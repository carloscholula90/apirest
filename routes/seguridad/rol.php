<?php  
use App\Http\Controllers\Api\seguridad\RolController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 


Route::post('/generaReporte', [RolController::class, 'generaReporte']);
Route::get('/', [RolController::class, 'index']);
Route::get('/{idRol}', [RolController::class, 'show']);
Route::post('/create', [RolController::class, 'store']);
Route::put('/{idRol}', [RolController::class, 'update']);
Route::delete('/{idRol}', [RolController::class, 'destroy']);
Route::post('/imprimeXls', [RolController::class, 'exportaExcel']);   