<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use App\Models\escolar\DetalleTurno;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DetalleTurnoController extends Controller
{
    public function index()
    {
        $detallesTurno = DetalleTurno::orderBy('idTurno')
            ->orderBy('idDtlTurno')
            ->get();

        return $this->returnData('detallesTurno', $detallesTurno, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idTurno' => 'required|integer',
            'diaSemana' => 'required|max:255',
            'horaInicio' => 'required|date_format:H:i',
            'horaFin' => 'required|date_format:H:i|after:horaInicio',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $maxId = DetalleTurno::max('idDtlTurno');
        $newId = $maxId ? $maxId + 1 : 1;

        try {
            $detalleTurno = DetalleTurno::create([
                'idDtlTurno' => $newId,
                'idTurno' => $request->idTurno,
                'diaSemana' => strtoupper(trim($request->diaSemana)),
                'horaInicio' => $request->horaInicio,
                'horaFin' => $request->horaFin,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('El detalle del turno ya existe o el turno no es valido', 400, null);
            }

            return $this->returnEstatus('Error al insertar el detalle del turno', 400, null);
        }

        if (!$detalleTurno) {
            return $this->returnEstatus('Error al crear el detalle del turno', 500, null);
        }

        return $this->returnData('detalleTurno', $detalleTurno, 200);
    }

    public function show($idTurno)
    {
        $detalleTurno = DetalleTurno::find($idTurno);

        if (!$detalleTurno) {
            return $this->returnEstatus('Detalle de turno no encontrado', 404, null);
        }

        return $this->returnData('detalleTurno', $detalleTurno, 200);
    }

    public function update(Request $request, $idDtlTurno)
    {
        $detalleTurno = DetalleTurno::find($idDtlTurno);

        if (!$detalleTurno) {
            return $this->returnEstatus('Detalle de turno no encontrado', 404, null);
        }

        $validator = Validator::make($request->all(), [
            'idTurno' => 'required|integer',
            'diaSemana' => 'required|max:255',
            'horaInicio' => 'required|date_format:H:i',
            'horaFin' => 'required|date_format:H:i|after:horaInicio',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $detalleTurno->idTurno = $request->idTurno;
        $detalleTurno->diaSemana = strtoupper(trim($request->diaSemana));
        $detalleTurno->horaInicio = $request->horaInicio;
        $detalleTurno->horaFin = $request->horaFin;
        $detalleTurno->save();

        return $this->returnData('detalleTurno', $detalleTurno, 200);
    }

    public function destroy($idDtlTurno)
    {
        $detalleTurno = DetalleTurno::find($idDtlTurno);

        if (!$detalleTurno) {
            return $this->returnEstatus('Detalle de turno no encontrado', 404, null);
        }

        try {
            $detalleTurno->delete();
            return $this->returnEstatus('Detalle de turno eliminado', 200, null);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('No se puede eliminar el detalle del turno, esta siendo utilizado', 400, null);
            }

            return $this->returnEstatus('Error al eliminar el detalle del turno', 400, null);
        }
    }

    public function generaReporte()
    {
        return $this->imprimeCtl(
            'detallesturno',
            ' detalles de turno ',
            ['CLAVE', 'TURNO', 'DIA', 'HORA INICIO', 'HORA FIN'],
            [80, 80, 160, 100, 100],
            'idTurno'
        );
    }

    public function exportaExcel()
    {
        return $this->exportaXLS(
            'detallesturno',
            'idDtlTurno',
            ['CLAVE', 'TURNO', 'DIA', 'HORA INICIO', 'HORA FIN'],
            'idTurno'
        );
    }
}
