<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use App\Models\tesoreria\Beca;
use App\Models\tesoreria\BecaAlumno;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;  
use Maatwebsite\Excel\Facades\Excel;

class BecasAlumnoController extends Controller{

    protected $pdfController;

    // Inyección de la clase PdfReportGenerator
    public function __construct(pdfController $pdfController)
    {
        $this->pdfController = $pdfController;
    }


    public function index(){       
        $becas = DB::table('becaAlumno as bc')
                                            ->select(
                                                'niv.idNivel',
                                                'niv.descripcion as nivel',
                                                'bc.idPeriodo as idPeriodo',
                                                'p.descripcion as periodo',
                                                'al.uid',
                                                'ca.idCarrera as idCarrera',
                                                'ca.descripcion as carrera',
                                                'bc.importeCole',
                                                'bc.importeInsc',
                                                'pers.nombre',
                                                'pers.primerApellido',
                                                'pers.segundoApellido',
                                                'b.descripcion AS beca',
                                                'b.idBeca'
                                            )
                                            ->join('alumno as al', function ($join) {
                                                $join->on('al.uid', '=', 'bc.uid')
                                                    ->on('al.secuencia', '=', 'bc.secuencia');
                                            })
                                            ->join('persona as pers', 'pers.uid', '=', 'al.uid')
                                            ->join('beca as b', 'b.idBeca', '=', 'bc.idBeca')
                                            
                                            ->join('nivel as niv', 'niv.idNivel', '=', 'al.idNivel')
                                            ->join('periodo as p', function ($join) {
                                                $join->on('p.idNivel', '=', 'bc.idNivel')
                                                    ->on('p.idPeriodo', '=', 'bc.idPeriodo');
                                            })
                                            ->join('carrera as ca', function ($join) {
                                                $join->on('ca.idNivel', '=', 'al.idNivel')
                                                    ->on('ca.idCarrera', '=', 'al.idCarrera');
                                            })
                                            ->get();
        return $this->returnData('becas',$becas,200);
    }   

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
                    'idNivel' => 'required|max:255',
                    'idPeriodo' => 'required|max:255',
                    'idBeca' => 'required|max:255',
                    'uid' => 'required|max:255',
                    'secuencia' => 'required|max:255',
                    'importeInsc' => 'required|max:255',
                    'importeCole' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        try {
            $becas = BecaAlumno::create([
                            'idNivel' => $request->idNivel,                           
                            'idPeriodo' => $request->idPeriodo,                           
                            'idBeca' => $request->idBeca,                                                                                                           
                            'uid' => $request->uid,
                            'importeInsc' => $request->importeInsc,
                            'secuencia' => $request->secuencia,    
                            'importeCole' =>  $request->importeCole,
                            'fechaAlta' => Carbon::now(),
                            'fechaModificacion' => Carbon::now()
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('La Beca ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar la Beca',400,null);
        }

        if (!$becas) 
            return $this->returnEstatus('Error al crear la Beca',500,null); 

        $datos = $this->obtenerSemestre($uid,$secuencia);       
        DB::statement("CALL ActualizaCargosInscrip(?, ?, ?, ?, ? ,?)", [$idNivel,$idPeriodo,
                                                         $uid,$secuencia, $datos->semestre, $datos->idTurno]);
      
        return $this->returnData('becas',$becas,200);   
    }

    public function show($idBeca){
        try {
            $$becas = Beca::findOrFail($idBeca);
            return $this->returnData('becas',$becas,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Beca no encontrado',404,null); 
        }
    }
    
    public function destroy($idNivel,$idPeriodo,$uid,$secuencia){
         $elininar = DB::table('becaAlumno')
                        ->where('idNivel', $idNivel)
                        ->where('idPeriodo', $idPeriodo)
                        ->where('uid', $uid)
                        ->where('secuencia', $secuencia);   
         
          if (!$elininar) 
            return $this->returnEstatus('Beca no encontrada',404,null);             
        $elininar->delete();
              
        $datos = $this->obtenerSemestre($uid,$secuencia);       
        DB::statement("CALL ActualizaCargosInscrip(?, ?, ?, ?, ? ,?)", [$idNivel,$idPeriodo,
                                                         $uid,$secuencia, $datos->semestre, $datos->idTurno]);
      
        return $this->returnEstatus('Beca eliminada',200,null); 
    }

    public function obtenerSemestre($uid,$secuencia){
        return DB::table('alumno as al')
                        ->join('nivel as niv', 'niv.idNivel', '=', 'al.idNivel')
                        ->join('periodo as per', function($join) {
                            $join->on('per.idNivel', '=', 'al.idNivel')
                                ->where('per.activo', 1);
                        })
                        ->join('ciclos as cl', function($join) {
                            $join->on('cl.uid', '=', 'al.uid')
                                ->on('cl.secuencia', '=', 'al.secuencia')
                                ->on('cl.idPeriodo', '=', 'per.idPeriodo')
                                ->whereRaw('cl.indexCiclo = (
                                    SELECT MIN(c2.indexCiclo)
                                    FROM ciclos c2
                                    WHERE c2.uid = al.uid
                                    AND c2.secuencia = al.secuencia
                                    AND c2.idPeriodo = per.idPeriodo
                                )');
                        })
                        ->join('turno as t', function($join) {
                            $join->on('t.letra', '=', DB::raw('SUBSTRING(cl.grupo, 3, 1)'));
                        })
                        ->where('al.uid', $uid)
                        ->where('al.secuencia', $secuencia)
                        ->select('t.idTurno', 'cl.semestre')
                        ->first();
    }

    public function update(Request $request){
        $becasAlumno =   DB::table('becaAlumno')
                        ->where('idNivel', $request->idNivel)
                        ->where('idPeriodo', $request->idPeriodo)
                        ->where('uid', $request->uid)
                        ->where('secuencia', $request->secuencia)
                        ->update([
                            'idBeca'            => $request->idBeca,
                            'importeCole'       => $request->importeCole,
                            'importeInsc'       => $request->importeInsc,
                            'fechaModificacion' => Carbon::now(),
                        ]);

        $datos = $this->obtenerSemestre($uid,$secuencia);       
        DB::statement("CALL ActualizaCargosInscrip(?, ?, ?, ?, ? ,?)", [$idNivel,$idPeriodo,
                                                         $uid,$secuencia, $datos->semestre, $datos->idTurno]);
                    
        return $this->returnData('Beca',"Actualizado ",200);
    }

      // Función para generar el reporte de personas
      public function generaReporte()
      {
        $becas = DB::table('becaAlumno as bc')
                                            ->select(
                                                'niv.idNivel',
                                                'niv.descripcion as nivel',
                                                'bc.idPeriodo as idPeriodo',
                                                'p.descripcion as periodo',
                                                'al.uid',
                                                'ca.idCarrera as idCarrera',
                                                'ca.descripcion as carrera',
                                                'bc.importeCole',
                                                'bc.importeInsc', 
                                                 DB::raw('CONCAT(pers.primerApellido, " ", pers.segundoApellido, " ", pers.nombre) AS nombre'),
                                                'b.descripcion AS beca',
                                                'b.idBeca'
                                            )
                                            ->join('alumno as al', function ($join) {
                                                $join->on('al.uid', '=', 'bc.uid')
                                                    ->on('al.secuencia', '=', 'bc.secuencia');
                                            })
                                            ->join('beca as b', 'b.idBeca', '=', 'bc.idBeca')
                                            ->join('persona as pers', 'pers.uid', '=', 'al.uid')
                                            ->join('nivel as niv', 'niv.idNivel', '=', 'al.idNivel')
                                            ->join('periodo as p', function ($join) {
                                                $join->on('p.idNivel', '=', 'bc.idNivel')
                                                    ->on('p.idPeriodo', '=', 'bc.idPeriodo');
                                            })
                                            ->join('carrera as ca', function ($join) {
                                                $join->on('ca.idNivel', '=', 'al.idNivel')
                                                    ->on('ca.idCarrera', '=', 'al.idCarrera');
                                            })
                                            ->get();
             
         // Si no hay personas, devolver un mensaje de error
         if ($becas->isEmpty())
             return $this->returnEstatus('No se encontraron datos para generar el reporte',404,null);
        
          // Convertir los datos a un formato de arreglo asociativo
        $dataArray = $becas->map(function ($item) {
            return (array) $item;
        })->toArray();      
         
         $headers = ['NIVEL','PERIODO','UID','NOMBRE','BECA','COLEGIATURA','INSCRIPCION'];
         $columnWidths = [90,100,80,200,100,100,100];   
         $keys = ['nivel', 'periodo','uid','nombre','beca','importeCole','importeInsc'];
        
        return $this->pdfController->generateReport($dataArray,$columnWidths,$keys , 'REPORTE DE BECAS', $headers,'L','Legal','rptBecas'. rand(1, 100).'.pdf');
       
     }  

     public function exportaExcel() {
        // Ruta del archivo a almacenar en el disco público
        $nombreRpt='becasAlumnos_rpt'.rand(1, 100).'.xlsx';
        $path = storage_path('app/public/'.$nombreRpt);
        $selectColumns = ['niv.descripcion as nivel',
                          'p.descripcion as periodo','al.uid',
                          DB::raw('CONCAT(pers.primerApellido, " ", pers.segundoApellido, " ", pers.nombre) AS nombre'),
                          'b.descripcion AS beca',
                          'bc.importeCole',
                          'bc.importeInsc']; // Seleccionar columnas específicas
        $namesColumns = ['NIVEL','PERIODO','UID','NOMBRE','BECA','COLEGIATURA','INSCRIPCION'];
        $joins = [
                 ['table' => 'alumno as al', // Tabla a unir
                   'type' => 'inner', // Tipo de JOIN (en este caso LEFT JOIN)
                   'conditions' => [
                        ['first' => 'al.uid', 'second' => 'bc.uid'],
                        ['first' => 'al.secuencia', 'second' => 'bc.secuencia']
                    ]
                  ],
                  ['table' => 'beca as b', // Tabla a unir
                  'conditions' => [
                        ['first' => 'b.idBeca', 'second' => 'bc.idBeca']
                  ],
                  'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ],
                ['table' => 'persona as pers', // Tabla a unir
                    'conditions' => [
                        ['first' => 'pers.uid', 'second' => 'al.uid']
                    ],
                  'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ],
                ['table' => 'nivel as niv', // Tabla a unir 'conditions' => [
                    'conditions' => [
                        ['first' => 'niv.idNivel', 'second' => 'al.idNivel']
                    ],
                    'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ],
                [ 'table' => 'periodo as p', // Tabla a unir
                  'conditions' => [
                        ['first' => 'p.idNivel', 'second' => 'al.idNivel'],
                         ['first' => 'p.idPeriodo', 'second' => 'bc.idPeriodo']
                  ],
                  'type' => 'inner'
                ],
                [ 'table' => 'carrera as ca', // Tabla a unir
                    'conditions' => [
                        ['first' => 'p.idNivel', 'second' => 'ca.idNivel'],
                        ['first' => 'ca.idCarrera', 'second' => 'al.idCarrera']
                    ],
                  'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ]
            ];
                                 
        $export = new GenericTableExportEsp('becaAlumno as bc', '', [], ['p.idNivel','p.idPeriodo','ca.idCarrera','al.uid'], ['asc','asc','asc','asc'], $selectColumns, $joins,$namesColumns);
  
        Excel::store($export, $nombreRpt, 'public');
       
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/'.$nombreRpt // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }
     }
}
