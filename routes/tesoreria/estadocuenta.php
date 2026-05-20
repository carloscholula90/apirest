<?php
use App\Http\Controllers\Api\tesoreria\EstadoCuentaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::group([], function () {   

    Route::get(
        '/folios/{uid}/{matricula}/{tipoEdoCta}',
        [EstadoCuentaController::class, 'obtenerFolios']
    )
    ->whereNumber('uid')
    ->whereNumber('matricula')
    ->whereNumber('tipoEdoCta');

    Route::get(
        '/validarQR/{uid}/{qr}',
        [EstadoCuentaController::class, 'validarQR']
    )
    ->whereNumber('uid');

    Route::get(
        '/generaReporte/{uid}/{idPeriodo}/{matricula}/{tipoEdoCta}',
        [EstadoCuentaController::class, 'generaReporte']
    )
    ->whereNumber('uid')
    ->whereNumber('idPeriodo')
    ->whereNumber('matricula')
    ->whereNumber('tipoEdoCta');

    Route::get(
        '/pagos/{uid}/{secuencia}/{idPeriodo}',
        [EstadoCuentaController::class, 'getAbonos']
    )
    ->whereNumber('uid')
    ->whereNumber('secuencia')
    ->whereNumber('idPeriodo');

    Route::post(
        '/abonos',
        [EstadoCuentaController::class, 'guardarMovtos']
    );

    Route::post(
        '/actualizaColegiatura',
        [EstadoCuentaController::class, 'actualizaColegiatura']
    );

    Route::post(
        '/create',
        [EstadoCuentaController::class, 'store']
    );

    Route::get(
        '/recibo',
        [EstadoCuentaController::class, 'recibo']
    );

    Route::delete(
        '/{uid}/{secuencia}/{idPeriodo}/{consecutivo}/{uidcajero}',
        [EstadoCuentaController::class, 'destroy']
    )
    ->whereNumber('uid')
    ->whereNumber('secuencia')
    ->whereNumber('idPeriodo')
    ->whereNumber('consecutivo');

    Route::get(
        '/{uid}/{idPeriodo}/{matricula}/{tipoEdoCta}',
        [EstadoCuentaController::class, 'index']
    )
    ->whereNumber('uid')
    ->whereNumber('idPeriodo')
    ->whereNumber('matricula')
    ->whereNumber('tipoEdoCta');

});