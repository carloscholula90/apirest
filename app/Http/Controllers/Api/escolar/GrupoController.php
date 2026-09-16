<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Grupo;
use Illuminate\Http\Request;  
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;  

class GrupoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       $grupos = Grupo::all();
       return $this->returnData('grupos',$grupos,200);
    }
     /**
     * Display a listing of the resource.
     */
    public function show($idNivel,$idPeriodo,$idCarrera)
    {
       $carreraFormatted = str_pad($idCarrera, 2, '0', STR_PAD_LEFT);
       $grupos = DB::table('grupos')
                                ->distinct()  
                                ->select('grupo')
                                ->where('idNivel', $idNivel)
                                ->where('idPeriodo',$idPeriodo)
                                ->where('grupo', 'like', $carreraFormatted.'%')
                                ->get();
       return $this->returnData('grupos',$grupos,200);
    }

    public function gruposSemestre($idNivel,$idPeriodo,$idCarrera)
    {
       $carreraFormatted = str_pad($idCarrera, 2, '0', STR_PAD_LEFT);
       $grupos = DB::table('grupos')
                                ->distinct()
                                ->select(
                                   DB::raw("SUBSTRING(grupo, CASE WHEN LENGTH(grupo) = 4 THEN 3 ELSE 4 END, 1) AS semestre"),
                                   'grupo'
                                )
                                ->where('idNivel', $idNivel)
                                ->where('idPeriodo',$idPeriodo)
                                ->where('grupo', 'like', $carreraFormatted.'%')
                                ->orderBy('semestre')
                                ->orderBy('grupo')
                                ->get();
       return $this->returnData('grupos',$grupos,200);
    }

    public function gruposSemestrePorTurno($idNivel, $idPeriodo, $idCarrera, $idTurno)
    {
       $carreraFormatted = str_pad($idCarrera, 2, '0', STR_PAD_LEFT);
       $grupos = DB::table('grupos')
                                ->distinct()
                                ->select(
                                   DB::raw("SUBSTRING(grupo, CASE WHEN LENGTH(grupo) = 4 THEN 3 ELSE 4 END, 1) AS semestre"),
                                   'grupo'
                                )
                                ->where('idNivel', $idNivel)
                                ->where('idPeriodo', $idPeriodo)
                                ->where('idTurno', $idTurno)
                                ->where('grupo', 'like', $carreraFormatted.'%')
                                ->orderBy('semestre')
                                ->orderBy('grupo')
                                ->get();

       return $this->returnData('grupos', $grupos, 200);
    }

    public function alumnosInscritosGrupo($idNivel,$idPeriodo,$grupo)
    {
       $alumnos = DB::table('ciclos as c')
                                ->select(
                                   'a.uid',
                                   'a.secuencia',
                                   'a.matricula',
                                   'a.idCarrera',
                                   'car.descripcion as carrera',
                                   'c.idNivel',
                                   'c.idPeriodo',
                                   'c.semestre',
                                   'c.grupo',
                                   DB::raw("CONCAT(p.primerApellido, ' ', p.segundoApellido, ' ', p.nombre) AS nombre")
                                )
                                ->join('alumno as a', function ($join) {
                                   $join->on('a.uid', '=', 'c.uid')
                                        ->on('a.secuencia', '=', 'c.secuencia');
                                })
                                ->join('persona as p', 'p.uid', '=', 'a.uid')
                                ->leftJoin('carrera as car', function ($join) {
                                   $join->on('car.idCarrera', '=', 'a.idCarrera')
                                        ->on('car.idNivel', '=', 'a.idNivel');
                                })
                                ->where('c.idNivel', $idNivel)
                                ->where('c.idPeriodo',$idPeriodo)
                                ->where('c.grupo', $grupo)
                                ->orderBy('p.primerApellido')
                                ->orderBy('p.segundoApellido')
                                ->orderBy('p.nombre')
                                ->get();
       return $this->returnData('alumnos',$alumnos,200);
    }

    public function gruposProfesor($idPeriodo,$uidProfesor,$secuenciaProfesor = null)
    {
       $grupos = DB::table('grupos as g')
                                ->select(
                                   'g.gruposec',
                                   'g.idNivel',
                                   'n.descripcion as nivel',
                                   'g.idPeriodo',
                                   'p.descripcion as periodo',
                                   DB::raw("SUBSTRING(g.grupo, 1, CASE WHEN LENGTH(g.grupo) = 4 THEN 1 ELSE 2 END) AS idCarrera"),
                                   'car.descripcion as carrera',
                                   DB::raw("SUBSTRING(g.grupo, CASE WHEN LENGTH(g.grupo) = 4 THEN 3 ELSE 4 END, 1) AS semestre"),
                                   'g.grupo',
                                   'g.idAsignatura',
                                   'asig.descripcion as asignatura',
                                   'g.idTurno',
                                   't.descripcion as turno',
                                   'g.inscritos',
                                   'g.capacidad',
                                   'g.uidProfesor',
                                   'g.secuenciaProfesor',
                                   DB::raw("CONCAT(prof.primerApellido, ' ', prof.segundoApellido, ' ', prof.nombre) AS profesor")
                                )
                                ->leftJoin('nivel as n', 'n.idNivel', '=', 'g.idNivel')
                                ->leftJoin('periodo as p', function ($join) {
                                   $join->on('p.idNivel', '=', 'g.idNivel')
                                        ->on('p.idPeriodo', '=', 'g.idPeriodo');
                                })
                                ->leftJoin('carrera as car', function ($join) {
                                   $join->on('car.idNivel', '=', 'g.idNivel')
                                        ->on('car.idCarrera', '=', DB::raw("SUBSTRING(g.grupo, 1, CASE WHEN LENGTH(g.grupo) = 4 THEN 1 ELSE 2 END)"));
                                })
                                ->leftJoin('asignatura as asig', 'asig.idAsignatura', '=', 'g.idAsignatura')
                                ->leftJoin('turno as t', 't.idTurno', '=', 'g.idTurno')
                                ->leftJoin('persona as prof', 'prof.uid', '=', 'g.uidProfesor')
                                ->where('g.idPeriodo', $idPeriodo)
                                ->where('g.uidProfesor', $uidProfesor)
                                ->when($secuenciaProfesor !== null, function ($query) use ($secuenciaProfesor) {
                                   return $query->where('g.secuenciaProfesor', $secuenciaProfesor);
                                })
                                ->orderBy('g.idPeriodo', 'desc')
                                ->orderBy('g.grupo')
                                ->orderBy('g.idAsignatura')
                                ->get();
       return $this->returnData('grupos',$grupos,200);
    }

    public function gruposRelacionados($grupo)
    {
       $grupo = strtoupper(trim($grupo));
       $longitud = strlen($grupo);

       if ($longitud < 4) {
          return $this->returnEstatus(
             'El grupo no tiene un formato valido',
             400,
             null
          );
       }

       $longitudCarrera = ($longitud == 4) ? 1 : 2;
       $posTurno = $longitudCarrera + 1;
       $posSemestre = $longitudCarrera + 2;

       $carrera = substr($grupo, 0, $longitudCarrera);
       $turno = substr($grupo, $posTurno - 1, 1);
       $semestre = substr($grupo, $posSemestre - 1, 1);
       $grupoLetra = substr($grupo, $posSemestre);

       $grupos = DB::table('grupos as g')
                              ->distinct()
                              ->select(
                                 'g.idNivel',
                                 'n.descripcion as nivel',
                                 'g.idPeriodo',
                                 'p.descripcion as periodo',
                                 DB::raw("SUBSTRING(g.grupo, 1, CASE WHEN LENGTH(g.grupo) = 4 THEN 1 ELSE 2 END) AS idCarrera"),
                                 'car.descripcion as carrera',
                                 't.letra as turno',
                                 't.descripcion as descripcion',
                                 DB::raw("SUBSTRING(g.grupo, CASE WHEN LENGTH(g.grupo) = 4 THEN 3 ELSE 4 END, 1) AS semestre"),
                                 DB::raw("SUBSTRING(g.grupo, CASE WHEN LENGTH(g.grupo) = 4 THEN 4 ELSE 5 END) AS grupoLetra"),
                                 'g.grupo',
                                 'g.inscritos',
                                 'g.capacidad'
                              )
                              ->join('periodo as p', function ($join) {
                                 $join->on('p.idNivel', '=', 'g.idNivel')
                                      ->on('p.idPeriodo', '=', 'g.idPeriodo')
                                      ->where('p.activo', 1);
                              })
                              ->leftJoin('nivel as n', 'n.idNivel', '=', 'g.idNivel')
                              ->leftJoin('carrera as car', function ($join) {
                                 $join->on('car.idNivel', '=', 'g.idNivel')
                                      ->on('car.idCarrera', '=', DB::raw("SUBSTRING(g.grupo, 1, CASE WHEN LENGTH(g.grupo) = 4 THEN 1 ELSE 2 END)"));
                              })
                              ->join('turno as t', 't.letra', '=', DB::raw("SUBSTRING(g.grupo, CASE WHEN LENGTH(g.grupo) = 4 THEN 2 ELSE 3 END, 1)"))
                              ->whereRaw("SUBSTRING(g.grupo, 1, CASE WHEN LENGTH(g.grupo) = 4 THEN 1 ELSE 2 END) = ?", [$carrera])
                              ->whereRaw("SUBSTRING(g.grupo, CASE WHEN LENGTH(g.grupo) = 4 THEN 3 ELSE 4 END, 1) = ?", [$semestre])
                              ->where('g.grupo', '<>', $grupo)
                              ->orderBy('g.idNivel')
                              ->orderBy('g.idPeriodo')
                              ->orderBy('g.grupo')
                              ->get();

       return response()->json(
          $grupos->map(function ($grupo) {
             return [
                'grupo' => $grupo->grupo,
                'turno' => $grupo->turno,
                'descripcion' => $grupo->descripcion,
             ];
          })->values(),
          200
       );
    }

   public function cambioGrupo(Request $request)
{
    $datos = $request->validate([
        'newGrupo'          => ['required', 'string', 'size:5'],
        'newSemestre'       => ['required', 'integer', 'min:1'],
        'idNivel'           => ['required', 'integer'],
        'idPeriodo'         => ['required', 'integer'],
        'idCarrera'         => ['required', 'integer'],
        'uidMvto'           => ['required', 'string'],
        'grupo'             => ['required', 'string'],

        'grupos'             => ['required', 'array', 'min:1'],
        'grupos.*.uid'       => ['required', 'integer'],
        'grupos.*.secuencia' => ['required', 'integer'],
        'grupos.*.matricula' => ['required', 'integer'],
        'grupos.*.semestre'  => ['required', 'integer'],
        'grupos.*.idTurno'   => ['required', 'string', 'size:1'],
        'grupos.*.plan'      => ['required', 'string'],
    ]);

    $nuevoGrupo = strtoupper($datos['newGrupo']);
    $longitud = strlen($nuevoGrupo);

    /*
     * 11E5A:
     * posición 2 = E
     * posición 3 = 5
     *
     * Las posiciones de substr comienzan en cero.
     */
    $posicionTurno = $longitud === 4 ? 1 : 2;
    $posicionSemestre = $longitud === 4 ? 2 : 3;

    $nuevoTurno = substr($nuevoGrupo, $posicionTurno, 1);
    $semestreGrupo = (int) substr(
        $nuevoGrupo,
        $posicionSemestre,
        1
    );

    if ($semestreGrupo !== (int) $datos['newSemestre']) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'newSemestre' => [
                "El semestre enviado no coincide con el grupo {$nuevoGrupo}.",
            ],
        ]);
    }

    $periodo = DB::table('periodo as p')
        ->join('turno as t', function ($join) use ($nuevoTurno) {
            $join->where('t.letra', '=', $nuevoTurno);
        })
        ->select([
            'p.idPeriodo',
            't.idTurno',
        ])
        ->where('p.idPeriodo', $datos['idPeriodo'])
        ->where('p.idNivel', $datos['idNivel'])
        ->where('p.activo', 1)
        ->first();

    if (!$periodo) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'newGrupo' => [
                "No existe un periodo activo o no existe el turno {$nuevoTurno}.",
            ],
        ]);
    }

    DB::transaction(function () use (
        $datos,
        $nuevoGrupo,
        $nuevoTurno,
        $periodo
    ) {
        DB::statement("SET @origen = 'LARAVEL'");
        DB::statement('SET @uidMvto = ?', [
            $datos['uidMvto'],
        ]);

        foreach ($datos['grupos'] as $alumno) {
            $uid = $alumno['uid'];
            $secuencia = $alumno['secuencia'];

            $ciclo = DB::table('ciclos')
                ->where('idPeriodo', $datos['idPeriodo'])
                ->where('uid', $uid)
                ->where('secuencia', $secuencia)
                ->lockForUpdate()
                ->first();

            if (!$ciclo) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'grupos' => [
                        "No se encontró el ciclo del alumno {$uid}, secuencia {$secuencia}.",
                    ],
                ]);
            }

            $servicios = $this->obtenerServiciosTesoreria(
                $uid,
                $secuencia,
                $datos['idPeriodo']
            );

            if (!$servicios) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'grupos' => [
                        "No se encontraron servicios para el alumno {$uid}.",
                    ],
                ]);
            }

            /*
             * data_get funciona si $servicios es objeto o arreglo.
             */
            $idsServicios = array_values(array_filter([
                data_get($servicios, 'idServicioInscripcion'),
                data_get($servicios, 'idServicioColegiatura'),
                data_get($servicios, 'idServicioRecargo'),
            ], function ($idServicio) {
                return $idServicio !== null;
            }));

            if (empty($idsServicios)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'grupos' => [
                        "No se encontraron IDs de servicios para el alumno {$uid}.",
                    ],
                ]);
            }

            $colegiaturasPagadas = DB::table('edocta as edo')
                ->where('edo.uid', $uid)
                ->where('edo.secuencia', $secuencia)
                ->where('edo.idPeriodo', $datos['idPeriodo'])
                ->where('edo.tipomovto', 'A')
                ->whereIn('edo.idServicio', $idsServicios)
                ->exists();

            DB::table('ciclos')
                ->where('idPeriodo', $datos['idPeriodo'])
                ->where('uid', $uid)
                ->where('secuencia', $secuencia)
                ->update([
                    'grupo'    => $nuevoGrupo,
                    'semestre' => $datos['newSemestre'],
                ]);

            if (!$colegiaturasPagadas) {
                DB::table('edocta as edo')
                    ->where('edo.uid', $uid)
                    ->where('edo.secuencia', $secuencia)
                    ->where('edo.idPeriodo', $datos['idPeriodo'])
                    ->where('edo.tipomovto', 'C')
                    ->whereIn('edo.idServicio', $idsServicios)
                    ->delete();

                /*
                 * Se envía el turno nuevo E.
                 * Si el procedimiento espera un ID numérico,
                 * cambia $nuevoTurno por $periodo->idTurno.
                 */
                DB::select(
                    'CALL GeneraCargosInscrip(?, ?, ?, ?, ?, ?, ?)',
                    [
                        $datos['idNivel'],
                        $datos['idPeriodo'],
                        $datos['idCarrera'],
                        $datos['newSemestre'],
                        $uid,
                        $secuencia,
                        $nuevoTurno,
                    ]
                );
            }

            if ($ciclo->indexCiclo !== null) {
                DB::table('calificaciones')
                    ->where('indexCiclo', $ciclo->indexCiclo)
                    ->delete();

                DB::select(
                    'CALL GeneraCargaAcad(?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $datos['idNivel'],
                        $datos['idPeriodo'],
                        $uid,
                        $alumno['matricula'],
                        $datos['newSemestre'],
                        $datos['idCarrera'],
                        $alumno['plan'],
                        $nuevoGrupo,
                    ]
                );
            }
        }
    });

    return $this->returnData(
        'Registros actualizados',
        null,
        200
    );
}

private function obtenerServiciosTesoreria($uid,$secuencia,$idPeriodo){
    return DB::table('configuracionTesoreria as ct')
        ->join('alumno as al', function ($join) use ($uid, $secuencia) {
            $join->on('ct.idNivel', '=', 'al.idNivel')
                ->where('al.uid', '=', $uid)
                ->where('al.secuencia', '=', $secuencia);
        })
        ->selectRaw("? as idPeriodo", [$idPeriodo])
        ->addSelect(
            'al.idNivel',
            'ct.idServicioColegiatura',
            'ct.idServicioInscripcion',
            'ct.idServicioRecargo',
            'ct.idServicioNotaCredito',
            'ct.idServicioTraspasoSaldos1'
        )
        ->first();
    }


public function obtenerAsignaturas($grupo){
    $datos = DB::table('detasignatura as det')
                           ->select(
                                 'asig.descripcion',
                                 'gpo.inscritos',
                                 'asig.idAsignatura',
                                 'gpo.capacidad'
                           )
                           ->join('grupos as gpo', function($join) use ($grupo) {
                                 $join->on('det.idAsignatura', '=', 'gpo.idAsignatura')
                                    ->on('gpo.idNivel', '=', 'det.idNivel')
                                    ->whereRaw("
                                       SUBSTRING(gpo.grupo, 1,
                                             CASE 
                                                WHEN LENGTH(gpo.grupo) = 4 THEN 1
                                                WHEN LENGTH(gpo.grupo) = 5 THEN 2
                                                ELSE 2
                                             END
                                       ) = det.idCarrera
                                    ")
                                    ->where('gpo.grupo', $grupo);
                           })
                           ->join('asignatura as asig', 'asig.idAsignatura', '=', 'gpo.idAsignatura')
                           ->join('periodo as p', function($join) {
                                 $join->on('p.idNivel', '=', 'gpo.idNivel')
                                    ->on('gpo.idPeriodo', '=', 'p.idPeriodo')
                                    ->where('p.activo', 1);
                           })
                           ->orderBy('gpo.idAsignatura')
                           ->get();

         return $this->returnData('grupos',$datos,200);
   }

  public function actualizarActas(Request $request, $gruposec){
            $request->validate([
                           'uidSecretario' => 'required',
                           'uidPresidente' => 'required',
                           'uidVocal' => 'required',
                           'logoSep' => 'required',
                           'logoEscudo' => 'required',
                           'fechaIni' => 'required',
                           'horaIni' => 'required',
                           'idFormato' => 'required'
            ]);

            $grupo = Grupo::where('gruposec', $gruposec)
                           ->first();

            if (!$grupo) {
               return response()->json([
                     'message' => 'Grupo no encontrado'
               ], 404);
            }

            $grupo->update([
                     'uidSecretario' => $request->uidSecretario,
                     'uidPresidente' => $request->uidPresidente,
                     'uidPresidente' => $request->uidVocal,
                     'logoSep' => $request->logoSep,
                     'logoEscudo'=> $request->logoEscudo,
                     'idFormato' => $request->idFormato,
                     'fechaIni' => $request->fechaIni,
                     'horaIni' => $request->horaIni,
                     'horaFin' => $request->horaFin
            ]);

            $grupo->refresh();

            return response()->json([
               'message' => 'Profesor actualizado correctamente',
               'data'    => $grupo
            ], 200);
         }

         public function actualizarProfesor(Request $request, $gruposec){
            $request->validate([
               'uidProfesor' => 'required',
               'secuencia'   => 'required'
            ]);

            $grupo = Grupo::where('gruposec', $gruposec)
                           ->first();

            if (!$grupo) {
               return response()->json([
                     'message' => 'Grupo no encontrado'
               ], 404);
            }

            $grupo->update([
               'uidProfesor' => $request->uidProfesor,
               'secuenciaProfesor' => $request->secuencia
            ]);

            $grupo->refresh();

            return response()->json([
               'message' => 'Profesor actualizado correctamente',
               'data'    => $grupo
            ], 200);
         }



   public function exportaExcel(){
    // Ruta del archivo
    $path = storage_path('app/public/grupos_rpt.xlsx');

    // Columnas a seleccionar
    $selectColumns = [ 'periodo.idPeriodo','periodo.descripcion AS periodo',
                       'nivel.idNivel','nivel.descripcion AS nivel',
                       'grupos.grupo', 'carrera.descripcion AS carrera',
                       'grupos.idAsignatura', 'asignatura.descripcion AS asignatura',
                       'turno.descripcion AS turno','empleado.uid',
                        DB::raw("
                              CONCAT(
                                 persona.primerApellido, ' ',
                                 persona.segundoApellido, ' ',
                                 persona.nombre
                              ) AS nombre
                        "),
                       'grupos.inscritos',
                       DB::raw("
                              CONCAT(
                                 secretario.primerApellido, ' ',
                                 secretario.segundoApellido, ' ',
                                 secretario.nombre
                              ) AS secretario
                        "),
                        DB::raw("
                              CONCAT(
                                 supervisor.primerApellido, ' ',
                                 supervisor.segundoApellido, ' ',
                                 supervisor.nombre
                              ) AS supervisor
                        "),
                        DB::raw("
                              CONCAT(
                                 presidente.primerApellido, ' ',
                                 presidente.segundoApellido, ' ',
                                 presidente.nombre
                              ) AS presidente
                        "),
                        'grupos.fechaIni', 'grupos.horaIni', 'grupos.horaFin',
                        'grupos.logoSep','grupos.logoEscudo'
                     ];

    // Encabezados del Excel
    $namesColumns = [ 'ID PERIODO','PERIODO','ID NIVEL', 'NIVEL','GRUPO','CARRERA','ID ASIGNATURA',
                      'ASIGNATURA','TURNO','UID DOCENTE','DOCENTE','INSCRITOS','SECRETARIO','SUPERVISOR',
                      'PRESIDENTE','FECHA INICIO','HORA INICIO','HORA FIN','LOGO SEP','LOGO ESCUDO'];

   $joins = [
      // grupos
   [
        'table' => 'grupos',
        'type'  => 'inner',
        'conditions' => [
            [
                'first'  => 'grupos.idNivel',
                'second' => 'nivel.idNivel'
            ]
        ]
    ],
    // periodo
    [
        'table' => 'periodo',
        'type'  => 'inner',
        'conditions' => [
            [
                'first'  => 'periodo.activo',
                'second' => DB::raw('1')
            ],
             [
                'first'  => 'grupos.idPeriodo',
                'second' => 'periodo.idPeriodo'
            ],
             [
                'first'  => 'nivel.idNivel',
                'second' => 'periodo.idNivel'
            ]
        ]
    ],
    // carrera
    [
        'table' => 'carrera',
        'type'  => 'inner',
        'conditions' => [
            [
                'first'  => 'carrera.idNivel',
                'second' => 'nivel.idNivel'
            ],
            [
                'first'  => DB::raw("SUBSTRING(grupos.grupo, 1, CASE WHEN LENGTH(grupos.grupo) = 4 THEN 1 ELSE 2 END)"),
                'second' => 'carrera.idCarrera'
             ]
        ]
    ],
    // turno
    [
        'table' => 'turno',
        'type'  => 'inner',
        'conditions' => [
            [
                'first'  => 'turno.idTurno',
                'second' => 'grupos.idTurno'
            ]
        ]
    ],
    // asignatura
    [
        'table' => 'asignatura',
        'type'  => 'inner',
        'conditions' => [
            [
                'first'  => 'asignatura.idAsignatura',
                'second' => 'grupos.idAsignatura'
            ]
        ]
    ],
    // empleado
    [
        'table' => 'empleado',
        'type'  => 'left',
        'conditions' => [
            [
                'first'  => 'empleado.uid',
                'second' => 'grupos.uidProfesor'
            ]
        ]
    ],
    // persona docente
    [
        'table' => 'persona',
        'type'  => 'left',
        'conditions' => [
            [
                'first'  => 'persona.uid',
                'second' => 'empleado.uid'
            ]
        ]
    ],
    // secretario
    [
        'table' => 'persona as secretario',
        'type'  => 'left',
        'conditions' => [
            [
                'first'  => 'secretario.uid',
                'second' => 'grupos.uidSecretario'
            ]
        ]
    ],
    // supervisor
    [
        'table' => 'persona as supervisor',
        'type'  => 'left',
        'conditions' => [
            [
                'first'  => 'supervisor.uid',
                'second' => 'grupos.uidSupervisor'
            ]
        ]
    ],
    // presidente
    [
        'table' => 'persona as presidente',
        'type'  => 'left',
        'conditions' => [
            [
                'first'  => 'presidente.uid',
                'second' => 'grupos.uidPresidente'
            ]
           ]
       ]
   ];

    // Exportación
    $export = new GenericTableExportEsp('nivel','grupos.grupo',
                  [],['grupos.grupo'], ['asc'],$selectColumns, $joins, $namesColumns);

    // Guardar archivo
    Excel::store($export, 'grupos_rpt.xlsx', 'public');

    // Validar existencia
    if (file_exists($path)) {

        return response()->json([
            'status'  => 200,
            'message' => 'https://reportes.siaweb.com.mx/storage/app/public/grupos_rpt.xlsx'
        ]);

    } else {
        return response()->json([
            'status'  => 500,
            'message' => 'Error al generar el reporte'
        ]);
    }
}
}
