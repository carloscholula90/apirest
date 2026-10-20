<?php

use App\Http\Controllers\Api\auditoria\AuditoriaBajasController;
use Illuminate\Support\Facades\Route;

Route::get('/{uid}', [AuditoriaBajasController::class, 'bajas'])
    ->whereNumber('uid');
