<?php
use App\Http\Controllers\Api\tesoreria\CondonacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::get('/verificar-rutas-v2', function () {
    return response()->json([
        'version' => 'condonacion-v2'
    ]);
});


Route::get('/registros/{uid}/{secuencia}/{idPeriodo}', [CondonacionController::class, 'registros']);
Route::get('/reporte/{fechaInicio}/{fechaFin}', [CondonacionController::class, 'reporteCondonaciones']);
Route::get('/reporteXls/{fechaInicio}/{fechaFin}', [CondonacionController::class, 'reporteCondonacionesExcel']);
Route::delete('/eliminar/{uid}/{secuencia}/{idServicio}/{consecutivoEdocta}', [CondonacionController::class, 'eliminar']);
Route::get('/imprimeXls/{idFchInicio}/{idFechaFin}/{idCajero}', [CondonacionController::class, 'indexExcel']);
Route::post('/guardar', [CondonacionController::class, 'guardar']);
Route::get(
    '/{idFchInicio}/{idFechaFin}/{idCajero}',
    [CondonacionController::class, 'index']
)
->where('idFchInicio', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
->where('idFechaFin', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
->whereNumber('idCajero');