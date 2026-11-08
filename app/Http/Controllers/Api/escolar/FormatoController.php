<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use App\Models\escolar\Formato;

class FormatoController extends Controller
{
    public function index()
    {
        $formatos = Formato::orderBy('nombre')->get(['idFormato', 'nombre', 'ruta', 'default']);

        return $this->returnData('formatos', $formatos, 200);
    }
}
