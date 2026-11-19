<?php
use App\Http\Controllers\Api\tesoreria\EstadoCuentaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; 

Route::group([], function () {   

    Route::get(
        '/log/{uid}/{secuencia}',
        [EstadoCuentaController::class, 'buscarLogPorUidSecuencia']
    )
    ->whereNumber('uid')
    ->whereNumber('secuencia');

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

    // Abonos del estado de cuenta tipo 2 (apuntaba a getAbonos2, que no existe)
    Route::get(
        '/pagosEdo/{uid}/{secuencia}/{idPeriodo}',
        [EstadoCuentaController::class, 'getAbonosEdo']
    )
    ->whereNumber('uid')
    ->whereNumber('secuencia')
    ->whereNumber('idPeriodo');

    // Abonos de un tipo de estado de cuenta (1 o 2); tipoEdoCta se valida en el controlador
    Route::get(
        '/pagos/{uid}/{secuencia}/{idPeriodo}/{tipoEdoCta}',
        [EstadoCuentaController::class, 'getAbonos']
    )
    ->whereNumber('uid')
    ->whereNumber('secuencia')
    ->whereNumber('idPeriodo');

    // Sin tipo: todos los abonos (se conserva mientras las pantallas pasan a la ruta con tipo)
    Route::get(
        '/pagos/{uid}/{secuencia}/{idPeriodo}',
        [EstadoCuentaController::class, 'getAbonos']
    )
    ->whereNumber('uid')
    ->whereNumber('secuencia')
    ->whereNumber('idPeriodo');

    Route::post(
        '/abonos',
        [EstadoCuentaController::class, 'guardarMovtosServicios']
    );

    Route::post(
        '/reporteConcentradoMovimientos',
        [EstadoCuentaController::class, 'generarReporteConcentradoMovimientos']
    );

    Route::get(
        '/reporteConcentradoMovimientos/archivo/{archivo}',
        [EstadoCuentaController::class, 'descargarReporteConcentradoMovimientos']
    )
    ->where('archivo', 'concentrado-movimientos-[0-9a-fA-F-]+\.pdf')
    ->name('estadoscuenta.reporte-concentrado.archivo');

    Route::post(
        '/actualizaColegiatura',
        [EstadoCuentaController::class, 'actualizaColegiatura']
    );

    Route::post(
        '/create',
        [EstadoCuentaController::class, 'store']
    );

    Route::post(
        '/create-dinamico',
        [EstadoCuentaController::class, 'storeDinamico']
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
    ->whereNumber('consecutivo')
    ->whereNumber('uidcajero');

    Route::get(
        '/{uid}/{idPeriodo}/{matricula}/{tipoEdoCta}',
        [EstadoCuentaController::class, 'index']
    )
    ->whereNumber('uid')
    ->whereNumber('idPeriodo')
    ->whereNumber('matricula')
    ->whereNumber('tipoEdoCta');

});
