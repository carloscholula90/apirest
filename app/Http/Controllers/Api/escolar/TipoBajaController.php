<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use App\Models\escolar\TipoBaja;

class TipoBajaController extends Controller
{
    public function index()
    {
        $tiposBaja = TipoBaja::orderBy('idTipoBaja')->get();

        return $this->returnData('tiposBaja', $tiposBaja, 200);
    }
}
