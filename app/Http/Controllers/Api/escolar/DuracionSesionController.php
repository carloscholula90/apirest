<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use App\Models\escolar\DuracionSesion;

class DuracionSesionController extends Controller
{
    public function index()
    {
        $duraciones = DuracionSesion::orderBy('idNivel')->orderBy('idTurno')->get();

        return $this->returnData('duraciones', $duraciones, 200);
    }

    public function show($idNivel, $idTurno)
    {
        $duracion = DuracionSesion::minutos($idNivel, $idTurno);

        if ($duracion === null) {
            return $this->returnEstatus('No hay duracion de sesion configurada para el nivel y turno', 404, null);
        }

        return $this->returnData('duracion', $duracion, 200);
    }
}
