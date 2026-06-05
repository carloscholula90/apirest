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

    public function cambioGrupo(Request $request){

      $grupo = $request->newGrupo;
      $len = strlen($grupo);
      $posSemestre = ($len == 4) ? 3 : 4;
      $posTurno    = ($len == 4) ? 2 : 3;
      $semestre = substr($grupo, $posSemestre - 1, 1);
      $turno    = substr($grupo, $posTurno - 1, 1);


      $periodo = DB::table('periodo as p')
                 ->select('p.idPeriodo', 'p.idTurno', DB::raw("$semestre as semestre"))
                 ->join('turno as t', 't.letra', '=', DB::raw("'$turno'"))
                 ->where('p.activo', 1)
                 ->where('p.idNivel', $request->idNivel)
                 ->first();

      //Validar el turno de para ver si cambia de costos
      DB::statement("SET @origen = 'LARAVEL'");
      DB::statement("SET @uidMvto = ?", [$request->uidMvto]);
                    
      foreach ($request->grupos as $grupos){

         $ciclo = DB::table('ciclos')
               ->where('idPeriodo', $request->idPeriodo)
               ->where('uid', $grupos->uid)
               ->where('secuencia', $grupos->secuencia)
               ->first();

         $indexciclo = $ciclo->indexciclo ?? null;

         DB::table('ciclos')
                        ->where('idPeriodo', $request->idPeriodo)
                        ->where('uid',$grupos->uid)
                        ->where('secuencia', $grupos->secuencia)
                        ->update(['grupo' => $request->newGrupo,
                                 'semestre'=> $request->newSemestre]);

                 //valido si hay movimientos en el estado de cuenta
                 
            $colegiaturasPagadas = DB::table('edocta as edo')
                                       ->where('edo.uid', $grupos->uid)
                                       ->where('edo.secuencia', $grupos->secuencia)
                                       ->where('edo.idPeriodo', $servicios->idPeriodo)
                                       ->where('edo.tipomovto', 'A')
                                       ->whereIn('edo.idServicio', [
                                                $servicios->idServicioInscripcion,
                                                $servicios->idServicioColegiatura,
                                                $servicios->idServicioRecargo
                            ])
                            ->orderBy('edo.consecutivo', 'asc')
                            ->exists();

               //Cambio de grupo borramos materias
               if (!$colegiaturasPagadas) {
                     DB::table('edocta as edo')
                        ->where('edo.uid', $grupos->uid)
                        ->where('edo.secuencia', $grupos->secuencia)
                        ->where('edo.idPeriodo', $servicios->idPeriodo)
                        ->where('edo.tipomovto', 'C')
                        ->whereIn('edo.idServicio', [
                           $servicios->idServicioInscripcion,
                           $servicios->idServicioColegiatura,
                           $servicios->idServicioRecargo
                        ])
                        ->delete();

                     //Actualizamos cargos
                     $result = DB::select('CALL GeneraCargosInscrip(?, ?, ?, ?, ?, ?, ?)', 
                                                        [$request->idNivel,$request->idPeriodo, 
                                                         $request->idCarrera,$request->newSemestre,
                                                         $grupos->uid,$grupos->secuencia,$grupos->idTurno
                                                        ]);

               }
               if($indexciclo!=null){
                     DB::table('calificaciones')
                           ->where('indexCiclo', $indexciclo)
                           ->delete();

                     $result = DB::select('CALL GeneraCargaAcad(?, ?, ?, ?, ?, ?, ?, ?)', 
                                                        [$request->idNivel,$request->idPeriodo, 
                                                         $grupos->uid,$grupos->matricula,
                                                         $grupos->semestre,$request->idCarrera,
                                                         $grupos->plan,$request->grupo
                                                        ]);
               
               }

      }
      return $this->returnData('Registros actualizados',null,200);
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
            'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/grupos_rpt.xlsx'
        ]);

    } else {
        return response()->json([
            'status'  => 500,
            'message' => 'Error al generar el reporte'
        ]);
    }
}
}
