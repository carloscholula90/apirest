<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Api\escolar\Concerns\ManejaHorarios;
use App\Http\Controllers\Controller;
use App\Models\escolar\DefaultTurno;
use App\Models\escolar\DetalleTurno;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DefaultTurnoController extends Controller
{
    use ManejaHorarios;

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

        $error = $this->validaReglasTurno($request->idTurno, $request->idTipoBloque, $request->horaInicio, $request->horaFin);

        if ($error) {
            return $this->returnEstatus($error, 400, null);
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
            ->orderBy('horaInicio')
            ->get();

        if ($defaultTurnos->isEmpty()) {
            return $this->returnEstatus('Default de turno no encontrado', 404, null);
        }

        return $this->returnData('defaultTurnos', $defaultTurnos, 200);
    }

    public function update(Request $request, $idTurno, $idTipoBloque)
    {
        $defaultTurno = $this->buscaDefault($idTurno, $idTipoBloque)->first();

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

        $error = $this->validaReglasTurno($idTurno, $idTipoBloque, $request->horaInicio, $request->horaFin);

        if ($error) {
            return $this->returnEstatus($error, 400, null);
        }

        // La llave es compuesta (idTurno, idTipoBloque): se actualiza por query para no tocar otros bloques del turno
        $this->buscaDefault($idTurno, $idTipoBloque)->update([
            'horaInicio' => $request->horaInicio,
            'horaFin' => $request->horaFin,
            'activo' => $request->activo,
        ]);

        return $this->returnData('defaultTurno', $this->buscaDefault($idTurno, $idTipoBloque)->first(), 200);
    }

    public function destroy($idTurno, $idTipoBloque)
    {
        if (!$this->buscaDefault($idTurno, $idTipoBloque)->exists()) {
            return $this->returnEstatus('Default de turno no encontrado', 404, null);
        }

        try {
            $this->buscaDefault($idTurno, $idTipoBloque)->delete();
            return $this->returnEstatus('Default de turno eliminado', 200, null);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('No se puede eliminar el default del turno, esta siendo utilizado', 400, null);
            }

            return $this->returnEstatus('Error al eliminar el default del turno', 400, null);
        }
    }

    private function buscaDefault($idTurno, $idTipoBloque)
    {
        return DefaultTurno::where('idTurno', $idTurno)
            ->where('idTipoBloque', $idTipoBloque);
    }

    // El bloque aplica a todos los dias del turno: debe caber en el horario de cada dia y no empalmarse con otro default
    private function validaReglasTurno($idTurno, $idTipoBloque, $horaInicio, $horaFin)
    {
        $detalles = DetalleTurno::where('idTurno', $idTurno)->get();

        if ($detalles->isEmpty()) {
            return 'El turno no tiene dias definidos en el detalle de turno';
        }

        foreach ($detalles as $detalle) {
            if (!$this->horaDentroDe($horaInicio, $horaFin, $detalle->horaInicio, $detalle->horaFin)) {
                return 'El bloque debe quedar dentro del horario del turno (' . $this->diaNombre($detalle->diaSemana) . ' '
                    . substr($detalle->horaInicio, 0, 5) . ' - ' . substr($detalle->horaFin, 0, 5) . ')';
            }
        }

        $empalme = DefaultTurno::where('idTurno', $idTurno)
            ->where('idTipoBloque', '<>', $idTipoBloque)
            ->get()
            ->first(function ($otro) use ($horaInicio, $horaFin) {
                return $this->seEmpalman($horaInicio, $horaFin, $otro->horaInicio, $otro->horaFin);
            });

        if ($empalme) {
            return 'El bloque se empalma con otro default del turno (' . substr($empalme->horaInicio, 0, 5) . ' - ' . substr($empalme->horaFin, 0, 5) . ')';
        }

        return null;
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
