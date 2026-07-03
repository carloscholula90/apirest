<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use App\Models\escolar\HorarioProfesor;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HorarioProfesorController extends Controller
{
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

        $maxSecuencia = HorarioProfesor::where('uid', $request->uid)->max('secuencia');
        $newSecuencia = $maxSecuencia ? $maxSecuencia + 1 : 1;

        try {
            $horarioProfesor = HorarioProfesor::create([
                'uid' => $request->uid,
                'secuencia' => $newSecuencia,
                'fechaInicio' => $request->fechaInicio,
                'fechaFin' => $request->fechaFin,
                'diaSemana' => strtoupper(trim($request->diaSemana)),
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
        $horarioProfesor = HorarioProfesor::where('uid', $uid)
            ->where('secuencia', $secuencia)
            ->first();

        if (!$horarioProfesor) {
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

        $horarioProfesor->fechaInicio = $request->fechaInicio;
        $horarioProfesor->fechaFin = $request->fechaFin;
        $horarioProfesor->diaSemana = strtoupper(trim($request->diaSemana));
        $horarioProfesor->horaInicio = $request->horaInicio;
        $horarioProfesor->horaFin = $request->horaFin;
        $horarioProfesor->idTipoBloque = $request->idTipoBloque;
        $horarioProfesor->save();
        $horarioProfesor->load('tipoBloque');

        return $this->returnData('horarioProfesor', $horarioProfesor, 200);
    }

    public function destroy($uid, $secuencia)
    {
        $horarioProfesor = HorarioProfesor::where('uid', $uid)
            ->where('secuencia', $secuencia)
            ->first();

        if (!$horarioProfesor) {
            return $this->returnEstatus('Horario del profesor no encontrado', 404, null);
        }

        try {
            $horarioProfesor->delete();
            return $this->returnEstatus('Horario del profesor eliminado', 200, null);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('No se puede eliminar el horario del profesor, esta siendo utilizado', 400, null);
            }

            return $this->returnEstatus('Error al eliminar el horario del profesor', 400, null);
        }
    }
}
