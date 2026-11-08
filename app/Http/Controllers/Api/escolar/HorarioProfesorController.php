<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Api\escolar\Concerns\ManejaHorarios;
use App\Http\Controllers\Controller;
use App\Models\escolar\HorarioProfesor;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HorarioProfesorController extends Controller
{
    use ManejaHorarios;

    public function index()
    {
        $horariosProfesor = HorarioProfesor::with('tipoBloque')
            ->orderBy('uid')
            ->orderBy('secuencia')
            ->get();

        return $this->returnData('horariosProfesor', $horariosProfesor, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required|integer',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
            'diaSemana' => 'required|max:255',
            'horaInicio' => 'required|date_format:H:i',
            'horaFin' => 'required|date_format:H:i|after:horaInicio',
            'idTipoBloque' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $diaSemana = $this->diaNombre($request->diaSemana);

        if (!$diaSemana) {
            return $this->returnEstatus('El dia de la semana no es valido', 400, null);
        }

        $error = $this->validaTraslape($request->uid, $diaSemana, $request);

        if ($error) {
            return $this->returnEstatus($error, 400, null);
        }

        $maxSecuencia = HorarioProfesor::where('uid', $request->uid)->max('secuencia');
        $newSecuencia = $maxSecuencia ? $maxSecuencia + 1 : 1;

        try {
            $horarioProfesor = HorarioProfesor::create([
                'uid' => $request->uid,
                'secuencia' => $newSecuencia,
                'fechaInicio' => $request->fechaInicio,
                'fechaFin' => $request->fechaFin,
                'diaSemana' => $diaSemana,
                'horaInicio' => $request->horaInicio,
                'horaFin' => $request->horaFin,
                'idTipoBloque' => $request->idTipoBloque,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('El horario del profesor ya existe o algun dato no es valido', 400, null);
            }

            return $this->returnEstatus('Error al insertar el horario del profesor', 400, null);
        }

        if (!$horarioProfesor) {
            return $this->returnEstatus('Error al crear el horario del profesor', 500, null);
        }

        $horarioProfesor->load('tipoBloque');

        return $this->returnData('horarioProfesor', $horarioProfesor, 200);
    }

    public function show($uid)
    {
        $horariosProfesor = HorarioProfesor::with('tipoBloque')
            ->where('uid', $uid)
            ->orderBy('secuencia')
            ->get();

        if ($horariosProfesor->isEmpty()) {
            return $this->returnEstatus('Horario del profesor no encontrado', 404, null);
        }

        return $this->returnData('horariosProfesor', $horariosProfesor, 200);
    }

    public function update(Request $request, $uid, $secuencia)
    {
        if (!$this->buscaHorario($uid, $secuencia)->exists()) {
            return $this->returnEstatus('Horario del profesor no encontrado', 404, null);
        }

        $validator = Validator::make($request->all(), [
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
            'diaSemana' => 'required|max:255',
            'horaInicio' => 'required|date_format:H:i',
            'horaFin' => 'required|date_format:H:i|after:horaInicio',
            'idTipoBloque' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $diaSemana = $this->diaNombre($request->diaSemana);

        if (!$diaSemana) {
            return $this->returnEstatus('El dia de la semana no es valido', 400, null);
        }

        $error = $this->validaTraslape($uid, $diaSemana, $request, $secuencia);

        if ($error) {
            return $this->returnEstatus($error, 400, null);
        }

        // La llave es compuesta (uid, secuencia): se actualiza por query para no tocar horarios de otros profesores
        $this->buscaHorario($uid, $secuencia)->update([
            'fechaInicio' => $request->fechaInicio,
            'fechaFin' => $request->fechaFin,
            'diaSemana' => $diaSemana,
            'horaInicio' => $request->horaInicio,
            'horaFin' => $request->horaFin,
            'idTipoBloque' => $request->idTipoBloque,
        ]);

        $horarioProfesor = $this->buscaHorario($uid, $secuencia)->with('tipoBloque')->first();

        return $this->returnData('horarioProfesor', $horarioProfesor, 200);
    }

    public function destroy($uid, $secuencia)
    {
        if (!$this->buscaHorario($uid, $secuencia)->exists()) {
            return $this->returnEstatus('Horario del profesor no encontrado', 404, null);
        }

        try {
            $this->buscaHorario($uid, $secuencia)->delete();
            return $this->returnEstatus('Horario del profesor eliminado', 200, null);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('No se puede eliminar el horario del profesor, esta siendo utilizado', 400, null);
            }

            return $this->returnEstatus('Error al eliminar el horario del profesor', 400, null);
        }
    }

    private function buscaHorario($uid, $secuencia)
    {
        return HorarioProfesor::where('uid', $uid)
            ->where('secuencia', $secuencia);
    }

    // No se permiten dos actividades del mismo profesor, el mismo dia y en vigencias que se cruzan
    private function validaTraslape($uid, $diaSemana, Request $request, $excluirSecuencia = null)
    {
        $traslape = HorarioProfesor::with('tipoBloque')
            ->where('uid', $uid)
            ->where('fechaInicio', '<=', $request->fechaFin)
            ->where('fechaFin', '>=', $request->fechaInicio)
            ->when($excluirSecuencia, function ($query) use ($excluirSecuencia) {
                $query->where('secuencia', '<>', $excluirSecuencia);
            })
            ->get()
            ->first(function ($otro) use ($diaSemana, $request) {
                return $this->diaNombre($otro->diaSemana) === $diaSemana
                    && $this->seEmpalman($request->horaInicio, $request->horaFin, $otro->horaInicio, $otro->horaFin);
            });

        if (!$traslape) {
            return null;
        }

        $tipo = $traslape->tipoBloque ? trim($traslape->tipoBloque->descripcion) : 'otra actividad';

        return 'Se traslapa con ' . $tipo . ' del ' . $diaSemana . ' ('
            . substr($traslape->horaInicio, 0, 5) . ' - ' . substr($traslape->horaFin, 0, 5) . ')';
    }
}
