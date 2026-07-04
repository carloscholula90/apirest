<?php

namespace App\Http\Controllers\Api\nomina;

use App\Http\Controllers\Controller;
use App\Models\nomina\CalendarioPago;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CalendarioPagoController extends Controller
{
    public function index()
    {
        $calendariosPago = CalendarioPago::orderBy('idTurno')->get();

        return $this->returnData('calendariosPago', $calendariosPago, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idTurno' => 'required|integer',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
            'procesado' => 'required|integer|in:0,1',
            'fechaCreacion' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        try {
            $calendarioPago = CalendarioPago::create([
                'idTurno' => $request->idTurno,
                'fechaInicio' => $request->fechaInicio,
                'fechaFin' => $request->fechaFin,
                'procesado' => $request->procesado,
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

    public function show($idTurno)
    {
        $calendarioPago = CalendarioPago::find($idTurno);

        if (!$calendarioPago) {
            return $this->returnEstatus('Calendario de pago no encontrado', 404, null);
        }

        return $this->returnData('calendarioPago', $calendarioPago, 200);
    }

    public function update(Request $request, $idTurno)
    {
        $calendarioPago = CalendarioPago::find($idTurno);

        if (!$calendarioPago) {
            return $this->returnEstatus('Calendario de pago no encontrado', 404, null);
        }

        $validator = Validator::make($request->all(), [
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
            'procesado' => 'required|integer|in:0,1',
            'fechaCreacion' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $calendarioPago->fechaInicio = $request->fechaInicio;
        $calendarioPago->fechaFin = $request->fechaFin;
        $calendarioPago->procesado = $request->procesado;

        if ($request->has('fechaCreacion')) {
            $calendarioPago->fechaCreacion = $request->fechaCreacion;
        }

        $calendarioPago->save();

        return $this->returnData('calendarioPago', $calendarioPago, 200);
    }

    public function destroy($idTurno)
    {
        $calendarioPago = CalendarioPago::find($idTurno);

        if (!$calendarioPago) {
            return $this->returnEstatus('Calendario de pago no encontrado', 404, null);
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
}
