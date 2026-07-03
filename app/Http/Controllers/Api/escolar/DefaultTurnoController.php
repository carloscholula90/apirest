<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use App\Models\escolar\DefaultTurno;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DefaultTurnoController extends Controller
{
    public function index()
    {
        $defaultTurnos = DefaultTurno::orderBy('idTurno')
            ->orderBy('idTipoBloque')
            ->get();

        return $this->returnData('defaultTurnos', $defaultTurnos, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idTurno' => 'required|integer',
            'horaInicio' => 'required|date_format:H:i',
            'idTipoBloque' => 'required|integer',
            'horaFin' => 'required|date_format:H:i|after:horaInicio',
            'activo' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        try {
            $defaultTurno = DefaultTurno::create([
                'idTurno' => $request->idTurno,
                'horaInicio' => $request->horaInicio,
                'idTipoBloque' => $request->idTipoBloque,
                'horaFin' => $request->horaFin,
                'activo' => $request->activo,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('El default del turno ya existe o algun dato no es valido', 400, null);
            }

            return $this->returnEstatus('Error al insertar el default del turno', 400, null);
        }

        if (!$defaultTurno) {
            return $this->returnEstatus('Error al crear el default del turno', 500, null);
        }

        return $this->returnData('defaultTurno', $defaultTurno, 200);
    }

    public function show($idTurno)
    {
        $defaultTurnos = DefaultTurno::with('tipoBloque')
            ->where('idTurno', $idTurno)
            ->orderBy('idTipoBloque')
            ->get();

        if ($defaultTurnos->isEmpty()) {
            return $this->returnEstatus('Default de turno no encontrado', 404, null);
        }

        return $this->returnData('defaultTurnos', $defaultTurnos, 200);
    }

    public function update(Request $request, $idTurno, $idTipoBloque)
    {
        $defaultTurno = DefaultTurno::where('idTurno', $idTurno)
            ->where('idTipoBloque', $idTipoBloque)
            ->first();

        if (!$defaultTurno) {
            return $this->returnEstatus('Default de turno no encontrado', 404, null);
        }

        $validator = Validator::make($request->all(), [
            'horaInicio' => 'required|date_format:H:i',
            'horaFin' => 'required|date_format:H:i|after:horaInicio',
            'activo' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $defaultTurno->horaInicio = $request->horaInicio;
        $defaultTurno->horaFin = $request->horaFin;
        $defaultTurno->activo = $request->activo;
        $defaultTurno->save();

        return $this->returnData('defaultTurno', $defaultTurno, 200);
    }

    public function destroy($idTurno, $idTipoBloque)
    {
        $defaultTurno = DefaultTurno::where('idTurno', $idTurno)
            ->where('idTipoBloque', $idTipoBloque)
            ->first();

        if (!$defaultTurno) {
            return $this->returnEstatus('Default de turno no encontrado', 404, null);
        }

        try {
            $defaultTurno->delete();
            return $this->returnEstatus('Default de turno eliminado', 200, null);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('No se puede eliminar el default del turno, esta siendo utilizado', 400, null);
            }

            return $this->returnEstatus('Error al eliminar el default del turno', 400, null);
        }
    }

    public function generaReporte()
    {
        return $this->imprimeCtl(
            'defaultTurno',
            ' default de turnos ',
            ['TURNO', 'HORA INICIO', 'TIPO BLOQUE', 'HORA FIN', 'ACTIVO'],
            [80, 120, 100, 120, 80],
            'idTurno'
        );
    }

    public function exportaExcel()
    {
        return $this->exportaXLS(
            'defaultTurno',
            'idTurno',
            ['TURNO', 'HORA INICIO', 'TIPO BLOQUE', 'HORA FIN', 'ACTIVO'],
            'idTurno'
        );
    }
}
