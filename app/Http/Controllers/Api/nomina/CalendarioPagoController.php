<?php

namespace App\Http\Controllers\Api\nomina;

use App\Http\Controllers\Controller;
use App\Models\nomina\CalendarioPago;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CalendarioPagoController extends Controller
{
    // ?pendientes=1 devuelve solo los calendarios que aun no se procesan
    public function index(Request $request)
    {
        $calendariosPago = CalendarioPago::with('turno')
            ->when($request->boolean('pendientes'), function ($query) {
                $query->where('procesado', 0);
            })
            ->orderBy('fechaInicio')
            ->orderBy('idTurno')
            ->get();

        return $this->returnData('calendariosPago', $calendariosPago, 200);
    }

    public function porTurno($idTurno)
    {
        $calendariosPago = CalendarioPago::with('turno')
            ->where('idTurno', $idTurno)
            ->orderBy('fechaInicio')
            ->get();

        return $this->returnData('calendariosPago', $calendariosPago, 200);
    }

    // Siguiente calendario por fecha que aun no se procesa; es el que se muestra por default
    public function siguiente()
    {
        $calendarioPago = CalendarioPago::with('turno')
            ->where('procesado', 0)
            ->orderBy('fechaInicio')
            ->orderBy('idTurno')
            ->first();

        if (!$calendarioPago) {
            return $this->returnEstatus('No hay calendarios de pago por procesar', 404, null);
        }

        return $this->returnData('calendarioPago', $calendarioPago, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idTurno' => 'required|integer',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after:fechaInicio',
            'procesado' => 'nullable|integer|in:0,1',
            'fechaCreacion' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $empalme = $this->buscaEmpalme($request->idTurno, $request->fechaInicio, $request->fechaFin);

        if ($empalme) {
            return $this->returnEstatus($empalme, 400, null);
        }

        try {
            $calendarioPago = CalendarioPago::create([
                'idTurno' => $request->idTurno,
                'fechaInicio' => $request->fechaInicio,
                'fechaFin' => $request->fechaFin,
                'procesado' => $request->procesado ?? 0,
                'fechaCreacion' => $request->fechaCreacion ?? now(),
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('El calendario de pago ya existe o algun dato no es valido', 400, null);
            }

            return $this->returnEstatus('Error al insertar el calendario de pago', 400, null);
        }

        if (!$calendarioPago) {
            return $this->returnEstatus('Error al crear el calendario de pago', 500, null);
        }

        return $this->returnData('calendarioPago', $calendarioPago, 200);
    }

    public function show($idCalendarioPago)
    {
        $calendarioPago = CalendarioPago::with('turno')->find($idCalendarioPago);

        if (!$calendarioPago) {
            return $this->returnEstatus('Calendario de pago no encontrado', 404, null);
        }

        return $this->returnData('calendarioPago', $calendarioPago, 200);
    }

    public function update(Request $request, $idCalendarioPago)
    {
        $calendarioPago = CalendarioPago::find($idCalendarioPago);

        if (!$calendarioPago) {
            return $this->returnEstatus('Calendario de pago no encontrado', 404, null);
        }

        if ($calendarioPago->procesado) {
            return $this->returnEstatus('El calendario de pago ya fue procesado y no se puede modificar', 400, null);
        }

        $validator = Validator::make($request->all(), [
            'idTurno' => 'nullable|integer',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after:fechaInicio',
            'procesado' => 'required|integer|in:0,1',
            'fechaCreacion' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $idTurno = $request->idTurno ?? $calendarioPago->idTurno;
        $empalme = $this->buscaEmpalme($idTurno, $request->fechaInicio, $request->fechaFin, $calendarioPago->idCalendarioPago);

        if ($empalme) {
            return $this->returnEstatus($empalme, 400, null);
        }

        $calendarioPago->idTurno = $idTurno;
        $calendarioPago->fechaInicio = $request->fechaInicio;
        $calendarioPago->fechaFin = $request->fechaFin;
        $calendarioPago->procesado = $request->procesado;

        if ($request->has('fechaCreacion')) {
            $calendarioPago->fechaCreacion = $request->fechaCreacion;
        }

        $calendarioPago->save();

        return $this->returnData('calendarioPago', $calendarioPago, 200);
    }

    public function destroy($idCalendarioPago)
    {
        $calendarioPago = CalendarioPago::find($idCalendarioPago);

        if (!$calendarioPago) {
            return $this->returnEstatus('Calendario de pago no encontrado', 404, null);
        }

        if ($calendarioPago->procesado) {
            return $this->returnEstatus('El calendario de pago ya fue procesado y no se puede eliminar', 400, null);
        }

        try {
            $calendarioPago->delete();
            return $this->returnEstatus('Calendario de pago eliminado', 200, null);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('No se puede eliminar el calendario de pago, esta siendo utilizado', 400, null);
            }

            return $this->returnEstatus('Error al eliminar el calendario de pago', 400, null);
        }
    }

    // Dos calendarios del mismo turno no pueden cubrir fechas en comun
    private function buscaEmpalme($idTurno, $fechaInicio, $fechaFin, $excluirIdCalendarioPago = null)
    {
        $empalme = CalendarioPago::where('idTurno', $idTurno)
            ->where('fechaInicio', '<=', $fechaFin)
            ->where('fechaFin', '>=', $fechaInicio)
            ->when($excluirIdCalendarioPago, function ($query) use ($excluirIdCalendarioPago) {
                $query->where('idCalendarioPago', '<>', $excluirIdCalendarioPago);
            })
            ->first();

        if (!$empalme) {
            return null;
        }

        return 'Se empalma con el calendario del ' . date('d/m/Y', strtotime($empalme->fechaInicio))
            . ' al ' . date('d/m/Y', strtotime($empalme->fechaFin)) . ' del mismo turno';
    }
}
