<?php

namespace App\Http\Controllers\Api\general;

use App\Http\Controllers\Controller;
use App\Models\general\Entrada;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EntradaController extends Controller
{
    public function index()
    {
        $entradas = Entrada::orderBy('id_entrada')->get();

        return $this->returnData('entradas', $entradas, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|max:255',
            'ip' => 'required|max:255',
            'estatus' => 'required|integer',
            'fechaAlta' => 'nullable|date',
            'fechaModificacion' => 'nullable|date',
            'contrasena' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $maxId = Entrada::max('id_entrada');
        $newId = $maxId ? $maxId + 1 : 1;

        try {
            $entrada = Entrada::create([
                'id_entrada' => $newId,
                'nombre' => strtoupper(trim($request->nombre)),
                'ip' => trim($request->ip),
                'estatus' => $request->estatus,
                'fechaAlta' => $request->fechaAlta ?? now(),
                'fechaModificacion' => $request->fechaModificacion ?? now(),
                'contrasena' => $request->contrasena,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('La entrada ya existe o algun dato no es valido', 400, null);
            }

            return $this->returnEstatus('Error al insertar la entrada', 400, null);
        }

        if (!$entrada) {
            return $this->returnEstatus('Error al crear la entrada', 500, null);
        }

        return $this->returnData('entrada', $entrada, 200);
    }

    public function show($idEntrada)
    {
        $entrada = Entrada::find($idEntrada);

        if (!$entrada) {
            return $this->returnEstatus('Entrada no encontrada', 404, null);
        }

        return $this->returnData('entrada', $entrada, 200);
    }

    public function update(Request $request, $idEntrada)
    {
        $entrada = Entrada::find($idEntrada);

        if (!$entrada) {
            return $this->returnEstatus('Entrada no encontrada', 404, null);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|max:255',
            'ip' => 'required|max:255',
            'estatus' => 'required|integer',
            'fechaAlta' => 'nullable|date',
            'fechaModificacion' => 'nullable|date',
            'contrasena' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $entrada->nombre = strtoupper(trim($request->nombre));
        $entrada->ip = trim($request->ip);
        $entrada->estatus = $request->estatus;

        if ($request->has('fechaAlta')) {
            $entrada->fechaAlta = $request->fechaAlta;
        }

        $entrada->fechaModificacion = $request->fechaModificacion ?? now();
        $entrada->contrasena = $request->contrasena;
        $entrada->save();

        return $this->returnData('entrada', $entrada, 200);
    }

    public function destroy($idEntrada)
    {
        $entrada = Entrada::find($idEntrada);

        if (!$entrada) {
            return $this->returnEstatus('Entrada no encontrada', 404, null);
        }

        try {
            $entrada->delete();
            return $this->returnEstatus('Entrada eliminada', 200, null);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('No se puede eliminar la entrada, esta siendo utilizada', 400, null);
            }

            return $this->returnEstatus('Error al eliminar la entrada', 400, null);
        }
    }
}
