<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use App\Models\escolar\TipoBloque;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TipoBloqueController extends Controller
{
    public function index()
    {
        $tiposBloque = TipoBloque::orderBy('idTipoBloque')->get();

        return $this->returnData('tiposBloque', $tiposBloque, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
            'color' => 'required|max:255',
            'activo' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $maxId = TipoBloque::max('idTipoBloque');
        $newId = $maxId ? $maxId + 1 : 1;

        try {
            $tipoBloque = TipoBloque::create([
                'idTipoBloque' => $newId,
                'descripcion' => strtoupper(trim($request->descripcion)),
                'color' => trim($request->color),
                'activo' => $request->activo,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('El tipo de bloque ya existe', 400, null);
            }

            return $this->returnEstatus('Error al insertar el tipo de bloque', 400, null);
        }

        if (!$tipoBloque) {
            return $this->returnEstatus('Error al crear el tipo de bloque', 500, null);
        }

        return $this->returnData('tipoBloque', $tipoBloque, 200);
    }

    public function show($idTipoBloque)
    {
        $tipoBloque = TipoBloque::find($idTipoBloque);

        if (!$tipoBloque) {
            return $this->returnEstatus('Tipo de bloque no encontrado', 404, null);
        }

        return $this->returnData('tipoBloque', $tipoBloque, 200);
    }

    public function update(Request $request, $idTipoBloque)
    {
        $tipoBloque = TipoBloque::find($idTipoBloque);

        if (!$tipoBloque) {
            return $this->returnEstatus('Tipo de bloque no encontrado', 404, null);
        }

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
            'color' => 'required|max:255',
            'activo' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $tipoBloque->descripcion = strtoupper(trim($request->descripcion));
        $tipoBloque->color = trim($request->color);
        $tipoBloque->activo = $request->activo;
        $tipoBloque->save();

        return $this->returnData('tipoBloque', $tipoBloque, 200);
    }

    public function destroy($idTipoBloque)
    {
        $tipoBloque = TipoBloque::find($idTipoBloque);

        if (!$tipoBloque) {
            return $this->returnEstatus('Tipo de bloque no encontrado', 404, null);
        }

        try {
            $tipoBloque->delete();
            return $this->returnEstatus('Tipo de bloque eliminado', 200, null);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('No se puede eliminar el tipo de bloque, esta siendo utilizado', 400, null);
            }

            return $this->returnEstatus('Error al eliminar el tipo de bloque', 400, null);
        }
    }

    public function generaReporte()
    {
        return $this->imprimeCtl(
            'tipoBloque',
            ' tipos de bloque ',
            ['CLAVE', 'DESCRIPCION', 'COLOR', 'ACTIVO'],
            [80, 200, 120, 80],
            'descripcion'
        );
    }

    public function exportaExcel()
    {
        return $this->exportaXLS(
            'tipoBloque',
            'idTipoBloque',
            ['CLAVE', 'DESCRIPCION', 'COLOR', 'ACTIVO'],
            'descripcion'
        );
    }
}
