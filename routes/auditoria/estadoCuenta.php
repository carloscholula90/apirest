<?php

use App\Http\Controllers\Api\auditoria\AuditoriaEstadoCuentaController;
use Illuminate\Support\Facades\Route;

Route::get('/{matricula}/{idPeriodo}', [AuditoriaEstadoCuentaController::class, 'estadoCuenta'])
    ->whereNumber('matricula')
    ->whereNumber('idPeriodo');
