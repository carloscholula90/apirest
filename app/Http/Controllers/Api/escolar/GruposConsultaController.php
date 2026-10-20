<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GruposConsultaController extends Controller
{
    public function show($idNivel, $idPeriodo, $idCarrera)
    {
        $validator = Validator::make(compact('idNivel', 'idPeriodo', 'idCarrera'), [
            'idNivel' => 'required|integer|min:1|max:2147483647',
            'idPeriodo' => 'required|integer|min:1|max:2147483647',
            'idCarrera' => 'required|integer|min:1|max:99',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 422, 'message' => 'Parametros de consulta invalidos.', 'errors' => $validator->errors()], 422);
        }

        $prefix = str_pad((string) (int) $idCarrera, 2, '0', STR_PAD_LEFT);
        $grupos = DB::table('grupos as g')
            ->leftJoin('turno as t', 't.idTurno', '=', 'g.idTurno')
            ->where('g.idNivel', (int) $idNivel)
            ->where('g.idPeriodo', (int) $idPeriodo)
            ->where('g.grupo', 'like', $prefix . '%')
            ->select('g.grupo', 'g.idTurno', 't.descripcion as turno')
            // La capacidad no se suma: cada fila representa una asignatura.
            ->selectRaw('MIN(g.capacidad) as capacidad')
            ->groupBy('g.grupo', 'g.idTurno', 't.descripcion')
            ->get()
            ->map(function ($row) {
                $code = trim($row->grupo);
                $semester = substr($code, strlen($code) === 4 ? 2 : 3, 1);
                return [
                    'semestre' => ctype_digit($semester) ? (int) $semester : null,
                    'grupo' => $row->grupo,
                    'capacidad' => $row->capacidad !== null ? (int) $row->capacidad : null,
                    'idTurno' => $row->idTurno !== null ? (int) $row->idTurno : null,
                    'turno' => $row->turno !== null ? trim($row->turno) : null,
                ];
            })
            // Primero por turno (1 matutino, 2 vespertino, ...), luego semestre y grupo
            ->sortBy([['idTurno', 'asc'], ['semestre', 'asc'], ['grupo', 'asc']])
            ->values();

        if ($grupos->isEmpty()) {
            return response()->json([
                'grupos' => [],
                'status' => 200,
                'message' => 'aun no hay grupos publicados para el periodo - verifica la oferta de grupos antes de continuar',
            ]);
        }

        return response()->json(['grupos' => $grupos, 'status' => 200]);
    }
}
