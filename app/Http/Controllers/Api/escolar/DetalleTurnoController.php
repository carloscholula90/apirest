<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Api\escolar\Concerns\ManejaHorarios;
use App\Http\Controllers\Controller;
use App\Models\escolar\DetalleTurno;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DetalleTurnoController extends Controller
{
    use ManejaHorarios;

    public function index()
    {
        $detallesTurno = DetalleTurno::orderBy('idTurno')
            ->orderBy('idDtlTurno')
            ->get();

        return $this->returnData('detallesTurno', $detallesTurno, 200);
    }

    public function store(Request $request)
    {
        // idNivel es opcional: sin nivel el día aplica a los niveles que no tengan el suyo
        $validator = Validator::make($request->all(), [
            'idTurno' => 'required|integer',
            'diaSemana' => 'required|max:255',
            'horaInicio' => 'required|date_format:H:i',
            'horaFin' => 'required|date_format:H:i|after:horaInicio',
            'idNivel' => 'nullable|integer|exists:nivel,idNivel',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $diaSemana = $this->diaNumero($request->diaSemana);

        if (!$diaSemana) {
            return $this->returnEstatus('El dia de la semana no es valido, usa 1 (domingo) a 7 (sabado)', 400, null);
        }

        if ($this->diaOcupado($request->idTurno, $request->idNivel, $diaSemana)) {
            return $this->returnEstatus('El dia ya tiene horario en este turno'.($request->idNivel ? ' para el nivel' : '').', no se permite el empalme de dias', 400, null);
        }

        $maxId = DetalleTurno::max('idDtlTurno');
        $newId = $maxId ? $maxId + 1 : 1;

        try {
            $detalleTurno = DetalleTurno::create([
                'idDtlTurno' => $newId,
                'idTurno' => $request->idTurno,
                'diaSemana' => (string) $diaSemana,
                'horaInicio' => $request->horaInicio,
                'horaFin' => $request->horaFin,
                'idNivel' => $request->idNivel,
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

    // Devuelve los dias configurados para el turno; con ?idNivel= solo los que aplican a ese nivel
    public function show(Request $request, $idTurno)
    {
        $detallesTurno = $request->filled('idNivel')
            ? DetalleTurno::paraNivel($idTurno, $request->query('idNivel'))
                ->sortBy(function ($detalle) { return (int) $detalle->diaSemana; })->values()
            : DetalleTurno::where('idTurno', $idTurno)
                ->orderByRaw('CAST(diaSemana AS UNSIGNED)')
                ->orderBy('idNivel')
                ->get();

        return $this->returnData('detallesTurno', $detallesTurno, 200);
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
            'idNivel' => 'nullable|integer|exists:nivel,idNivel',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $diaSemana = $this->diaNumero($request->diaSemana);

        if (!$diaSemana) {
            return $this->returnEstatus('El dia de la semana no es valido, usa 1 (domingo) a 7 (sabado)', 400, null);
        }

        // Si no llega idNivel se conserva el que tenía
        $idNivel = $request->has('idNivel') ? $request->idNivel : $detalleTurno->idNivel;

        if ($this->diaOcupado($request->idTurno, $idNivel, $diaSemana, $detalleTurno->idDtlTurno)) {
            return $this->returnEstatus('El dia ya tiene horario en este turno'.($idNivel ? ' para el nivel' : '').', no se permite el empalme de dias', 400, null);
        }

        $detalleTurno->idNivel = $idNivel;
        $detalleTurno->idTurno = $request->idTurno;
        $detalleTurno->diaSemana = (string) $diaSemana;
        $detalleTurno->horaInicio = $request->horaInicio;
        $detalleTurno->horaFin = $request->horaFin;
        $detalleTurno->save();

        return $this->returnData('detalleTurno', $detalleTurno, 200);
    }

    public function destroy($idTurno, $idDtlTurno)
    {
        $detalleTurno = DetalleTurno::where('idTurno', $idTurno)
            ->where('idDtlTurno', $idDtlTurno)
            ->first();

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

    // Un turno solo puede tener un registro por dia en cada nivel (o uno general sin nivel);
    // se comparan los dias ya guardados con o sin nombre
    private function diaOcupado($idTurno, $idNivel, $diaSemana, $excluirIdDtlTurno = null)
    {
        return DetalleTurno::where('idTurno', $idTurno)
            ->when($idNivel, function ($query) use ($idNivel) {
                $query->where('idNivel', $idNivel);
            }, function ($query) {
                $query->whereNull('idNivel');
            })
            ->when($excluirIdDtlTurno, function ($query) use ($excluirIdDtlTurno) {
                $query->where('idDtlTurno', '<>', $excluirIdDtlTurno);
            })
            ->get()
            ->contains(function ($detalle) use ($diaSemana) {
                return $this->diaNumero($detalle->diaSemana) === $diaSemana;
            });
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
