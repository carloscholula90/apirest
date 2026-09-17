<?php

namespace App\Http\Controllers\Api\tesoreria; 

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\tesoreria\ServicioCarrera;
use Carbon\Carbon;

class ServicioCarreraController extends Controller
{

      protected $pdfController;

    // Inyección de la clase PdfReportGenerator
    public function __construct(pdfController $pdfController)
    {
        $this->pdfController = $pdfController;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rows = DB::select("
             SELECT n.descripcion AS licenciatura,
                    sp.idNivel, p.idPeriodo, p.descripcion AS periodo,
                    s.idServicio, s.descripcion AS servicio,
                    sp.monto,s.cargoAutomatico, sp.semestre,
                    t.idTurno, t.descripcion as turno,
                    c.idCarrera, c.descripcion as carrera,sp.secuencia
            FROM servicio s
            INNER JOIN servicioCarrera sp ON sp.idServicio = s.idServicio
            INNER JOIN periodo p ON p.idNivel = sp.idNivel AND p.idPeriodo = sp.idPeriodo
                        AND (p.activo = 1 OR p.inscripciones = 1)             
            INNER JOIN carrera c ON sp.idCarrera = c.idCarrera AND sp.idNivel = c.idNivel
            INNER JOIN nivel n ON n.idNivel = sp.idNivel
            INNER JOIN turno t ON t.idTurno = sp.idTurno            
            ORDER BY n.idNivel, p.idPeriodo, c.idCarrera, s.idServicio, t.idTurno, sp.semestre");

    $estructura = [];

foreach ($rows as $row) {

    $nivelKey = $row->idNivel;
    $periodoKey = $row->idPeriodo;

    // Nivel
    if (!isset($estructura[$nivelKey])) {
        $estructura[$nivelKey] = [
            'nivel' => $row->licenciatura,
            'idNivel' => $row->idNivel,
            'periodos' => []
        ];
    }

    // Periodo
    if (!isset($estructura[$nivelKey]['periodos'][$periodoKey])) {
        $estructura[$nivelKey]['periodos'][$periodoKey] = [
            'idPeriodo' => $row->idPeriodo,
            'descripcion' => $row->periodo,
            'servicios' => []
        ];
    }

    // Servicio
    $estructura[$nivelKey]['periodos'][$periodoKey]['servicios'][] = [
        'idServicio' => $row->idServicio,
        'idNivel' => $row->idNivel,
        'descripcion' => $row->servicio,
        'cargoAutomatico' => $row->cargoAutomatico,
        'idTurno' => $row->idTurno,
        'idCarrera' => $row->idCarrera,
        'carrera' => $row->carrera,
        'turno' => $row->turno,
        'semestre' => $row->semestre,
        'monto' => $row->monto,
        'secuencia' => $row->secuencia
    ];
}

// Convertir periodos a arreglos indexados
foreach ($estructura as &$nivel) {
    $nivel['periodos'] = array_values($nivel['periodos']);
}
unset($nivel);

// Convertir niveles a arreglo indexado
$final = array_values($estructura);

return $final;
    return $final;
}

 public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'idServicio' => 'required|integer',
        'idNivel'    => 'required|integer',
        'idPeriodo'  => 'required|integer',
        'monto'      => 'required|numeric',
        'idCarrera'  => 'required|integer',
        'idTurno'    => 'required|integer',
        'semestre'   => 'required|integer',
        'aplicaIns'  => 'required|integer'
    ]);

    if ($validator->fails()) {
        return $this->returnEstatus(
            'Error en la validación de los datos',
            400,
            $validator->errors()
        );
    }

    $validacionRegla = $this->validarReglaNegocio($request);

    if ($validacionRegla !== true) {
        return $this->returnEstatus($validacionRegla, 400, null);
    }

    ServicioCarrera::create([
        'idNivel'=> $request->idNivel,
        'idPeriodo'=> $request->idPeriodo,
        'idServicio' => $request->idServicio,
        'idCarrera'=> $request->idCarrera,
        'idTurno'=> $request->idTurno,
        'semestre'=> $request->semestre,
        'monto'=> $request->monto,
        'aplicaIns'=> $request->aplicaIns,
        'fechaAlta'=> Carbon::now(),
        'fechaModificacion'=> Carbon::now()
    ]);

    $this->alumnosPorGrupo(
        $request->idNivel,
        $request->idPeriodo,
        $request->idCarrera,
        $request->idTurno,
        $request->semestre
    );

    return $this->returnData('servicios', null, 200);
}

    private function validarReglaNegocio(Request $request, $secuencia = null)
    {
        $base = ServicioCarrera::where('idNivel', $request->idNivel)
            ->where('idPeriodo', $request->idPeriodo)
            ->where('idServicio', $request->idServicio)
            ->where('idCarrera', $request->idCarrera);

        if ($secuencia !== null) {
            $base->where('secuencia', '<>', $secuencia);
        }

        $existeExacto = (clone $base)
            ->where('idTurno', $request->idTurno)
            ->where('semestre', $request->semestre)
            ->exists();

        if ($existeExacto) {
            return 'Ya existe un registro con la misma combinacion de nivel, periodo, carrera, servicio, turno y semestre.';
        }

        if ((int) $request->idTurno === 0) {
            $existenTurnosEspecificos = (clone $base)
                ->where('idTurno', '<>', 0)
                ->exists();

            if ($existenTurnosEspecificos) {
                return 'No se puede agregar el turno TODOS porque ya existen registros con turnos especificos para ese nivel, periodo, carrera y servicio.';
            }
        } else {
            $existeTodosTurnos = (clone $base)
                ->where('idTurno', 0)
                ->exists();

            if ($existeTodosTurnos) {
                return 'No se puede agregar un turno especifico porque ya existe un registro con turno TODOS para ese nivel, periodo, carrera y servicio.';
            }
        }

        if ((int) $request->semestre === 0) {
            $existenSemestresEspecificos = (clone $base)
                ->where('semestre', '<>', 0)
                ->exists();

            if ($existenSemestresEspecificos) {
                return 'No se puede agregar el semestre TODOS porque ya existen registros con semestres especificos para ese nivel, periodo, carrera y servicio.';
            }
        } else {
            $existeTodosSemestres = (clone $base)
                ->where('semestre', 0)
                ->exists();

            if ($existeTodosSemestres) {
                return 'No se puede agregar un semestre especifico porque ya existe un registro con semestre TODOS para ese nivel, periodo, carrera y servicio.';
            }
        }

        return true;
    }
    public function destroy($idNivel,$idPeriodo,$idServicio,$idCarrera,$idTurno)
    {
        // El semestre no viene en la ruta; se toma de los registros que se van
        // a borrar para poder recalcular sus grupos correspondientes después.
        $semestres = DB::table('servicioCarrera')
                            ->where('idNivel', $idNivel)
                            ->where('idPeriodo', $idPeriodo)
                            ->where('idServicio', $idServicio)
                            ->where('idCarrera', $idCarrera)
                            ->where('idTurno', $idTurno)
                            ->pluck('semestre')
                            ->unique();

        $destroy = DB::table('servicioCarrera')
                            ->where('idNivel', $idNivel  )
                            ->where('idPeriodo', $idPeriodo)
                            ->where('idServicio', $idServicio)
                            ->where('idCarrera', $idCarrera)
                            ->where('idTurno', $idTurno)
                            ->delete();

        if ($destroy == 0)
            return $this->returnEstatus('Error en la eliminacion', 404, null);

        foreach ($semestres as $semestre) {
            $this->alumnosPorGrupo($idNivel, $idPeriodo, $idCarrera, $idTurno, $semestre);
        }

        return $this->returnEstatus('Registro eliminado',200,null);
    }

     public function update(Request $request) {

        $validator = Validator::make($request->all(), [
                                   'idServicio' => 'required|numeric',
                                    'idNivel' => 'required|numeric',
                                    'idPeriodo' => 'required|numeric',
                                    'monto' => 'required|numeric',
                                    'secuencia' => 'required|numeric',
                                    'idCarrera' => 'required|numeric',
                                    'idTurno' => 'required|numeric',
                                    'semestre' => 'required|numeric',
                                    'aplicaIns' => 'required|numeric'
        ]);


        if ($validator->fails())
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors());

        $validacionRegla = $this->validarReglaNegocio($request, $request->secuencia);

        if ($validacionRegla !== true) {
            return $this->returnEstatus($validacionRegla, 400, null);
        }

        $filas= DB::table('servicioCarrera')
                            ->where('idNivel', $request->idNivel  )
                            ->where('idPeriodo', $request->idPeriodo)
                            ->where('idServicio', $request->idServicio)
                            ->where('idCarrera', $request->idCarrera)
                            ->where('idTurno', $request->idTurno)
                            ->where('semestre', $request->semestre)
                            ->where('secuencia', $request->secuencia)
                            ->update(['monto' => $request->monto,
                            'semestre' => $request->semestre,
                            'aplicaIns' => $request->aplicaIns
                                ]);
        
            $this->alumnosPorGrupo(
                $request->idNivel,
                $request->idPeriodo,
                $request->idCarrera,
                $request->idTurno,
                $request->semestre
            );

            return $this->returnData('datos actualizados',null,200);
       
    }

    // Función para generar el reporte de personas
      public function generaReporte()
      {
        $datos = $this->getDatos();  
     
         // Si no hay personas, devolver un mensaje de error
         if ($datos->isEmpty())
             return $this->returnEstatus('No se encontraron datos para generar el reporte',404,null);
         
         $headers = ['PERIODO','NIVEL','CARRERA','SERVICIO','SEM','TURNO','CARGO AUT.','MONTO'];
         $columnWidths = [80,90,190,200,30,80,50,50];   
         $keys = ['periodo','nivel','carrera','servicio','semestre','turno','cargoAutomatico','monto'];
        
         $datosArray = $datos->map(function ($item) {
            return (array) $item;
         })->toArray();  
     
         return $this->pdfController->generateReport($datosArray,$columnWidths,$keys , 'REPORTE DE SERVICIOS POR CARRERA', $headers,'L','letter','rptReporteServiciosXCarrera.pdf');
     }  

      public function getDatos(){
         $query = DB::table('servicio as s')
                        ->select(
                            'p.descripcion as periodo',
                            'n.descripcion as nivel',
                            'c.descripcion as carrera',
                            's.descripcion as servicio',
                            'sp.semestre',                                        
                            't.descripcion as turno',
                            DB::raw("CASE WHEN s.cargoAutomatico =1 THEN 'SI' ELSE 'NO' END AS cargoAutomatico"),
                            'sp.monto'
                        )
                        ->join('servicioCarrera as sp', 'sp.idServicio', '=', 's.idServicio')
                        ->join('carrera as c', function ($join) {
                            $join->on('sp.idCarrera', '=', 'c.idCarrera')
                                ->on('sp.idNivel', '=', 'c.idNivel');
                        })
                        ->join('nivel as n', 'n.idNivel', '=', 'sp.idNivel')
                        ->join('periodo as p', function ($join) {
                            $join->on('p.idNivel', '=', 'sp.idNivel')
                                ->on('p.idPeriodo', '=', 'sp.idPeriodo');
                        })
                        ->join('turno as t', 't.idTurno', '=', 'sp.idTurno')
                        ->where(function ($q) {
                            $q->where('p.activo', 1)
                            ->orWhere('p.inscripciones', 1);
                        })
                        ->orderBy('p.idPeriodo')
                        ->orderBy('n.idNivel')
                        ->orderBy('c.idCarrera')
                        ->orderBy('s.idServicio')
                        ->orderBy('t.idTurno')
                        ->orderBy('sp.semestre')
                        ->get();
        return $query;
    }


    public function exportaExcel() {  
        // Ruta del archivo a almacenar en el disco público
        $path = storage_path('app/public/serviciosCarrerasRpt.xlsx');
        $selectColumns = [  'periodo.descripcion as periodo','nivel.descripcion as nivel',
                            'carrera.descripcion as carrera',
                            'servicio.descripcion as servicio',
                            'servicioCarrera.semestre',                                        
                            'turno.descripcion as turno',
                            DB::raw("CASE WHEN servicio.cargoAutomatico =1 THEN 'SI' ELSE 'NO' END AS cargoAutomatico"),              
                    DB::raw("CONCAT('$', FORMAT(servicioCarrera.monto, 2)) as monto")]; // Seleccionar columnas específicas
        $namesColumns =['PERIODO','NIVEL','CARRERA','SERVICIO','SEM','TURNO','CARGO AUT.','MONTO'];
        
        $joins = [[ 'table' => 'servicio', // Tabla a unir
                    'first' => '', // Columna de la tabla principal
                    'conditions' => [
                        ['first' => 'servicio.idServicio', 'second' => 'servicioCarrera.idServicio']
                       ],                    
                    'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ],
                [ 'table' => 'nivel', // Tabla a unir
                'conditions' => [
                        ['first' => 'nivel.idNivel', 'second' => 'servicioCarrera.idNivel']
                     ] ,
                   'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ],
                [ 'table' => 'periodo', // Tabla a unir
                    'conditions' => [
                         ['first' => 'periodo.idNivel', 'second' => 'servicioCarrera.idNivel'],
                         ['first' => 'periodo.idPeriodo', 'second' => 'servicioCarrera.idPeriodo']
                    ],
                    'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ] ,
                [ 'table' => 'turno', // Tabla a unir
                   'conditions' => [
                         ['first' => 'turno.idTurno', 'second' => 'servicioCarrera.idTurno']
                   ],
                     'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ],
                [ 'table' => 'carrera', // Tabla a unir
                'conditions' => [
                         ['first' => 'periodo.idNivel', 'second' => 'servicioCarrera.idNivel'],
                         ['first' => 'periodo.idPeriodo', 'second' => 'servicioCarrera.idPeriodo']
                   ],
                   'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ]
                ];

        $export = new GenericTableExportEsp('servicioCarrera', '', [], ['periodo.idPeriodo','nivel.idNivel'], ['desc','asc'], $selectColumns, $joins,$namesColumns);

        // Guardar el archivo en el disco público
        Excel::store($export, 'serviciosCarrerasRpt.xlsx', 'public');
       
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {  
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/serviciosCarrerasRpt.xlsx' // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }  
    }

    public function copiarServicios(Request $request){
        try {
        // Ejecutar procedimiento
         // Validar datos
            $request->validate([
                'periodo_origen'  => 'required|integer',
                'periodo_destino' => 'required|integer',
                'id_carrera'      => 'required|integer',
            ]);

            DB::statement("CALL PCDCOPIASERVICIOS(?, ?, ?, @total, @mensaje)", [
                $request->periodo_origen,
                $request->periodo_destino,
                $request->id_carrera
            ]);

        // Obtener valores OUT
        $resultado = DB::select("SELECT @total AS registros_copiados, @mensaje AS resultado");

        return response()->json([
                'status' => 200,
                'message' => 'Se generaron los registros '
                    ]);
        
        } catch (\Exception $e) {
                return response()->json([
                'status' => 500,
                'message' => 'Error al generar los registros '.$e
                    ]);
    }
}

    /**
     * Método interno (ya no es un endpoint): se invoca desde store(), update()
     * y destroy() para regenerar, vía GeneraCargosInscrip, los cargos de los
     * alumnos activos de "ciclos" que coincidan con nivel, periodo, carrera,
     * turno y semestre de la regla de servicioCarrera que se acaba de
     * guardar/actualizar/borrar.
     *
     * "ciclos" no tiene columna de turno: va codificado en la posición 3 del
     * campo "grupo" (ej. 06D2B -> 06 carrera, D turno, 2 semestre, B letra de
     * grupo). Igual que en servicioCarrera, idTurno = 0 y semestre = 0
     * significan "todos" (no se filtra por ese campo).
     *
     * Devuelve un arreglo con el resultado (no una respuesta HTTP), ya que
     * ahora solo se llama internamente.
     */
    public function alumnosPorGrupo($idNivel, $idPeriodo, $idCarrera, $idTurno, $semestre)
    {
        $idNivel   = (int) $idNivel;
        $idPeriodo = (int) $idPeriodo;
        $idCarrera = (int) $idCarrera;
        $idTurno   = (int) $idTurno;
        $semestre  = (int) $semestre;

        $letraTurno = null;

        if ($idTurno !== 0) {
            $letraTurno = DB::table('turno')
                ->where('idTurno', $idTurno)
                ->value('letra');

            if (!$letraTurno) {
                return [
                    'error' => 'El turno indicado no existe o no tiene letra configurada',
                    'alumnos' => [],
                    'cargosGenerados' => 0,
                    'omitidos' => [],
                ];
            }
        }

        $query = DB::table('ciclos as cl')
            ->join('alumno as al', function ($j) {
                $j->on('al.uid', '=', 'cl.uid')
                  ->on('al.secuencia', '=', 'cl.secuencia');
            })
            ->join('persona as per', 'per.uid', '=', 'al.uid')
            ->join('carrera as ca', function ($j) {
                $j->on('ca.idNivel', '=', 'cl.idNivel')
                  ->on('ca.idCarrera', '=', 'al.idCarrera');
            })
            // Resuelve el turno REAL de cada alumno a partir de la letra en la
            // posición 3 de cl.grupo (necesario para GeneraCargosInscrip, sobre
            // todo cuando no se filtró por un turno específico, es decir idTurno=0).
            ->leftJoin('turno as tg', function ($j) {
                $j->whereRaw('tg.letra = SUBSTRING(cl.grupo, 3, 1)');
            })
            ->where('cl.idNivel', $idNivel)
            ->where('cl.idPeriodo', $idPeriodo)
            ->where('al.idCarrera', $idCarrera)
            ->where('al.activo', 1)
            ->select([   
                'al.uid',
                'al.matricula',
                'cl.secuencia',
                DB::raw("CONCAT(per.nombre,' ',per.primerApellido,' ',per.segundoApellido) AS nombre"),
                'ca.idCarrera',
                'ca.descripcion as carrera',
                'cl.grupo',
                'cl.semestre',
                'tg.idTurno as idTurnoAlumno',
            ]);

        if ($idTurno !== 0) {
            $query->whereRaw('SUBSTRING(cl.grupo, 3, 1) = ?', [$letraTurno]);
        }

        if ($semestre !== 0) {
            $query->where('cl.semestre', $semestre);
        }

        $alumnos = $query
            ->orderBy('cl.grupo')
            ->orderBy('per.primerApellido')
            ->orderBy('per.segundoApellido')
            ->get();

        // Por cada alumno encontrado, ejecuta GeneraCargosInscrip con su turno y
        // semestre reales (no $idTurno/$semestre de la petición, que pueden venir
        // en 0 = "todos").
        $cargosGenerados = 0;
        $omitidos = [];

        foreach ($alumnos as $alumno) {

            if (!$alumno->idTurnoAlumno) {
                $omitidos[] = [
                    'uid' => $alumno->uid,
                    'matricula' => $alumno->matricula,
                    'grupo' => $alumno->grupo,
                    'motivo' => 'No se encontró un turno cuya letra coincida con la posición 3 de "' . $alumno->grupo . '"',
                ];
                continue;
            }

            DB::statement("CALL GeneraCargosInscrip(?, ?, ?, ?, ?, ?, ?)", [
                $idNivel,
                $idPeriodo,
                $idCarrera,
                $alumno->semestre,
                $alumno->uid,
                $alumno->secuencia,
                $alumno->idTurnoAlumno,
            ]);

            $cargosGenerados++;
        }

        return [
            'alumnos' => $alumnos,
            'cargosGenerados' => $cargosGenerados,
            'omitidos' => $omitidos,
        ];
    }

}

