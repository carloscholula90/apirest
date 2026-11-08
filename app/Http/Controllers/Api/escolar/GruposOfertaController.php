<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GruposOfertaController extends Controller
{
    // id_campo de la tabla configuracion con el firmante predeterminado del acta
    private const FIRMANTES_CONFIG = [
        'secretario' => 3,
        'presidente' => 4,
        'supervisor' => 5,
    ];

    // Renglones de la oferta (un renglon por asignatura y grupo) con el estado de su acta y horario
    public function index(Request $request, $idNivel, $idPeriodo, $idCarrera)
    {
        $validator = Validator::make($request->query(), [
            'idTurno' => 'nullable|integer',
            'semestre' => 'nullable|integer',
            'grupo' => 'nullable|string|max:8',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $semestreSql = "SUBSTRING(g.grupo, CASE WHEN LENGTH(g.grupo) = 4 THEN 3 ELSE 4 END, 1)";

        $grupos = DB::table('grupos as g')
            ->select(
                'g.gruposec',
                'g.grupo',
                DB::raw($semestreSql.' AS semestre'),
                'g.idAsignatura',
                'asig.descripcion as asignatura',
                'g.idTurno',
                't.descripcion as turno',
                'g.inscritos',
                'g.capacidad',
                'g.uidProfesor',
                'g.secuenciaProfesor',
                'prof.primerApellido',
                'prof.segundoApellido',
                'prof.nombre',
                'g.uidSecretario',
                'g.uidPresidente',
                'g.uidSupervisor',
                'g.fechaIni',
                'g.horaIni',
                'g.horaFin',
                'g.idFormato'
            )
            ->selectSub(function ($query) {
                $query->from('duracionSesion as ds')
                    ->select('ds.duracion')
                    ->whereColumn('ds.idNivel', 'g.idNivel')
                    ->whereColumn('ds.idTurno', 'g.idTurno')
                    ->limit(1);
            }, 'duracionSesion')
            // horarios tiene un renglon por sesion del grupo
            ->selectSub(function ($query) {
                $query->from('horarios as h')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('h.grupoSec', 'g.gruposec');
            }, 'sesionesHorario')
            ->leftJoin('asignatura as asig', 'asig.idAsignatura', '=', 'g.idAsignatura')
            ->leftJoin('turno as t', 't.idTurno', '=', 'g.idTurno')
            ->leftJoin('persona as prof', 'prof.uid', '=', 'g.uidProfesor')
            ->where('g.idNivel', $idNivel)
            ->where('g.idPeriodo', $idPeriodo)
            // La carrera son los primeros 1 o 2 caracteres del grupo ('01D5A' -> 1)
            ->whereRaw("CAST(SUBSTRING(g.grupo, 1, CASE WHEN LENGTH(g.grupo) = 4 THEN 1 ELSE 2 END) AS UNSIGNED) = ?", [(int) $idCarrera])
            ->when($request->query('idTurno'), function ($query, $idTurno) {
                $query->where('g.idTurno', $idTurno);
            })
            ->when($request->query('semestre'), function ($query, $semestre) use ($semestreSql) {
                $query->whereRaw($semestreSql.' = ?', [$semestre]);
            })
            ->when($request->query('grupo'), function ($query, $grupo) {
                $query->where('g.grupo', trim($grupo));
            })
            ->orderBy('g.idTurno')
            ->orderByRaw($semestreSql)
            ->orderBy('g.grupo')
            ->orderBy('g.idAsignatura')
            ->get()
            ->map(function ($row) {
                return [
                    'gruposec' => $row->gruposec,
                    'grupo' => $row->grupo,
                    'semestre' => $row->semestre,
                    'idAsignatura' => $row->idAsignatura,
                    'asignatura' => $row->asignatura !== null ? trim($row->asignatura) : null,
                    'idTurno' => $row->idTurno,
                    'turno' => $row->turno !== null ? trim($row->turno) : null,
                    'inscritos' => (int) $row->inscritos,
                    'capacidad' => $row->capacidad !== null ? (int) $row->capacidad : null,
                    'uidProfesor' => $row->uidProfesor,
                    'secuenciaProfesor' => $row->secuenciaProfesor,
                    'profesor' => $this->nombreCompleto($row) ?: null,
                    'duracionSesion' => $row->duracionSesion !== null ? (int) $row->duracionSesion : null,
                    'actaCompleta' => $this->actaCompleta($row),
                    'horarioCapturado' => (int) $row->sesionesHorario > 0,
                ];
            })
            ->values();

        return $this->returnData('grupos', $grupos, 200);
    }

    // Lo guardado en el acta manda; cada firmante vacio toma el valor de configuracion
    public function acta($gruposec)
    {
        $grupo = $this->buscaGrupo($gruposec);

        if (!$grupo) {
            return $this->returnEstatus('Grupo no encontrado', 404, null);
        }

        $guardados = [
            'secretario' => $grupo->uidSecretario,
            'presidente' => $grupo->uidPresidente,
            'supervisor' => $grupo->uidSupervisor,
        ];

        // En configuracion el nombre del firmante esta en descripcion; valor solo trae el rol
        $predeterminados = DB::table('configuracion')
            ->whereIn('id_campo', self::FIRMANTES_CONFIG)
            ->pluck('descripcion', 'id_campo');

        $nombres = $this->nombresPersonas(
            array_merge([$grupo->uidProfesor], array_values($guardados))
        );

        $firmantes = [];

        foreach (self::FIRMANTES_CONFIG as $rol => $idCampo) {
            $guardado = !empty($guardados[$rol]);
            $predeterminado = trim((string) ($predeterminados[$idCampo] ?? ''));

            $firmantes[$rol] = [
                'uid' => $guardado ? $guardados[$rol] : null,
                'nombre' => $guardado ? ($nombres[$guardados[$rol]] ?? null) : ($predeterminado ?: null),
                'predeterminado' => !$guardado && $predeterminado !== '',
            ];
        }

        return $this->returnData('acta', [
            'gruposec' => (int) $gruposec,
            'grupo' => $grupo->grupo,
            'idAsignatura' => $grupo->idAsignatura,
            'profesor' => [
                'uid' => $grupo->uidProfesor,
                'secuencia' => $grupo->secuenciaProfesor,
                'nombre' => $nombres[$grupo->uidProfesor] ?? null,
            ],
            'secretario' => $firmantes['secretario'],
            'presidente' => $firmantes['presidente'],
            'supervisor' => $firmantes['supervisor'],
            'fechaIni' => $grupo->fechaIni,
            'horaIni' => $grupo->horaIni,
            'horaFin' => $grupo->horaFin,
            'idFormato' => $grupo->idFormato,
            'logoSep' => $grupo->logoSep,
            'logoEscudo' => $grupo->logoEscudo,
        ], 200);
    }

    public function actualizarCapacidad(Request $request, $gruposec)
    {
        $validator = Validator::make($request->all(), [
            'capacidad' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $grupo = $this->buscaGrupo($gruposec);

        if (!$grupo) {
            return $this->returnEstatus('Grupo no encontrado', 404, null);
        }

        if ((int) $request->capacidad < (int) $grupo->inscritos) {
            return $this->returnEstatus('La capacidad no puede ser menor a los '.(int) $grupo->inscritos.' alumnos inscritos', 400, null);
        }

        DB::table('grupos')->where('gruposec', $gruposec)->update(['capacidad' => $request->capacidad]);

        return $this->returnEstatus('Capacidad actualizada', 200, null);
    }

    public function actualizarLogotipo(Request $request, $gruposec)
    {
        $validator = Validator::make($request->all(), [
            'logoSep' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        if (!$this->buscaGrupo($gruposec)) {
            return $this->returnEstatus('Grupo no encontrado', 404, null);
        }

        DB::table('grupos')->where('gruposec', $gruposec)->update(['logoSep' => $request->logoSep]);

        return $this->returnEstatus('Logotipo actualizado', 200, null);
    }

    // Elimina la asignatura de la oferta junto con su horario; no aplica si tiene alumnos inscritos
    public function destroy($gruposec)
    {
        $grupo = $this->buscaGrupo($gruposec);

        if (!$grupo) {
            return $this->returnEstatus('Grupo no encontrado', 404, null);
        }

        if ((int) $grupo->inscritos > 0) {
            return $this->returnEstatus('No se puede eliminar: tiene '.(int) $grupo->inscritos.' alumnos inscritos', 400, null);
        }

        if (DB::table('horarios')->where('grupoSec', $gruposec)->where('impartido', 1)->exists()) {
            return $this->returnEstatus('No se puede eliminar: tiene sesiones impartidas', 400, null);
        }

        try {
            DB::transaction(function () use ($gruposec) {
                DB::table('horarios')->where('grupoSec', $gruposec)->delete();
                DB::table('grupos')->where('gruposec', $gruposec)->delete();
            });
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return $this->returnEstatus('No se puede eliminar el grupo, esta siendo utilizado', 400, null);
            }

            return $this->returnEstatus('Error al eliminar el grupo', 500, null);
        }

        return $this->returnEstatus('Grupo eliminado', 200, null);
    }

    private function buscaGrupo($gruposec)
    {
        return DB::table('grupos')->where('gruposec', $gruposec)->first();
    }

    private function actaCompleta($row)
    {
        return !empty($row->uidProfesor)
            && !empty($row->uidSecretario)
            && !empty($row->uidPresidente)
            && !empty($row->uidSupervisor)
            && !empty($row->fechaIni)
            && !empty($row->horaIni)
            && !empty($row->horaFin);
    }

    private function nombreCompleto($persona)
    {
        $partes = [$persona->primerApellido, $persona->segundoApellido, $persona->nombre];

        return trim(implode(' ', array_filter(array_map('trim', $partes))));
    }

    private function nombresPersonas(array $uids)
    {
        return DB::table('persona')
            ->whereIn('uid', array_filter($uids))
            ->get(['uid', 'primerApellido', 'segundoApellido', 'nombre'])
            ->mapWithKeys(function ($persona) {
                return [$persona->uid => $this->nombreCompleto($persona)];
            })
            ->all();
    }
}
