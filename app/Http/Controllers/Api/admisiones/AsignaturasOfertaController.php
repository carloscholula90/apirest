<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AsignaturasOfertaController extends Controller
{
    public function show($idNivel, $idPeriodo, $idCarrera, $grupo = null)
    {
        $validator = Validator::make(compact('idNivel', 'idPeriodo', 'idCarrera', 'grupo'), [
            'idNivel' => 'required|integer|min:1|max:2147483647',
            'idPeriodo' => 'required|integer|min:1|max:2147483647',
            'idCarrera' => 'required|integer|min:1|max:99',
            'grupo' => 'nullable|string|max:8',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 422, 'message' => 'Parametros de consulta invalidos.', 'errors' => $validator->errors()], 422);
        }

        $prefix = str_pad((string) (int) $idCarrera, 2, '0', STR_PAD_LEFT);
        $asignaturas = DB::table('grupos as g')
            ->join('asignatura as a', 'a.idAsignatura', '=', 'g.idAsignatura')
            ->where('g.idNivel', (int) $idNivel)
            ->where('g.idPeriodo', (int) $idPeriodo)
            ->where(function ($query) use ($prefix, $idCarrera) {
                $query->where('g.grupo', 'like', $prefix . '%');
                // Formato anterior: una cifra de carrera en grupos de cuatro caracteres.
                if ((int) $idCarrera < 10) {
                    $query->orWhere(function ($legacy) use ($idCarrera) {
                        $legacy->whereRaw('LENGTH(g.grupo) = 4')
                            ->where('g.grupo', 'like', (string) (int) $idCarrera . '%');
                    });
                }
            })
            ->whereExists(function ($query) use ($idCarrera) {
                $query->selectRaw('1')->from('detasignatura as d')
                    ->whereColumn('d.idAsignatura', 'g.idAsignatura')
                    ->whereColumn('d.idNivel', 'g.idNivel')
                    ->where('d.idCarrera', (int) $idCarrera);
            })
            ->when($grupo !== null, fn ($query) => $query->where('g.grupo', trim($grupo)))
            ->select('a.idAsignatura', 'a.descripcion')
            ->distinct()
            ->orderBy('a.descripcion')
            ->orderBy('a.idAsignatura')
            ->get();

        $response = ['asignaturas' => $asignaturas, 'status' => 200];
        if ($asignaturas->isEmpty()) {
            $response['message'] = 'No hay asignaturas publicadas para los filtros indicados.';
        }
        return response()->json($response);
    }
}
