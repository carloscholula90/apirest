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
                    c.idCarrera, c.descripcion as carrera
            FROM servicio s
            INNER JOIN servicioCarrera sp ON sp.idServicio = s.idServicio
            INNER JOIN carrera c ON sp.idCarrera = c.idCarrera AND sp.idNivel = c.idNivel
            INNER JOIN nivel n ON n.idNivel = sp.idNivel
            INNER JOIN periodo p ON p.idNivel = sp.idNivel AND p.idPeriodo = sp.idPeriodo
            INNER JOIN turno t ON t.idTurno = sp.idTurno
            WHERE (p.activo = 1 OR p.inscripciones = 1)  
            ORDER BY n.idNivel, p.idPeriodo, c.idCarrera, s.idServicio, t.idTurno, sp.semestre");

    $estructura = [];

    foreach ($rows as $row) {
    if (!isset($estructura[$row->licenciatura])) {
        $estructura[$row->licenciatura] = [
            'nivel' => $row->licenciatura,
            'idNivel' => $row->idNivel,
            'periodos' => []
        ];
    }

    $periodos =& $estructura[$row->licenciatura]['periodos'];

    if (!isset($periodos[$row->idPeriodo])) {
        $periodos[$row->idPeriodo] = [
            'idPeriodo' => $row->idPeriodo,
            'descripcion' => $row->periodo,
            'servicios' => []
        ];
    }

    $periodos[$row->idPeriodo]['servicios'][] = [
        'idServicio' => $row->idServicio,
        'idNivel'=> $row->idNivel,
        'descripcion' => $row->servicio,
        'cargoAutomatico' => $row->cargoAutomatico,
        'idTurno' => $row->idTurno,
        'idCarrera' => $row->idCarrera,
        'carrera' => $row->carrera,
        'turno' => $row->turno,
        'semestre' => $row->semestre,
        'monto'=>  $row->monto
        ];
    }

        // Convertir a arrays indexados
        $final = array_values(array_map(function($licenciatura) {
            $licenciatura['periodos'] = array_values($licenciatura['periodos']);
            return $licenciatura;
        }, $estructura));
    return $final;
}

 public function store(Request $request) {
        
        $validator = Validator::make($request->all(), [
                                    'idServicio' => 'required|numeric',
                                    'idNivel' => 'required|numeric',
                                    'idPeriodo' => 'required|numeric',
                                    'monto' => 'required|numeric',
                                    'idCarrera' => 'required|numeric',
                                    'idTurno' => 'required|numeric',
                                    'semestre' => 'required|numeric',
                                    'aplicaIns' => 'required|numeric'
        ]);   
       
        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors());
       
        $servicioC = ServicioCarrera::create([
                                'idNivel'=> $request->idNivel,
                                'idPeriodo'=> $request->idPeriodo,
                                'idServicio' => $request->idServicio,
                                'idCarrera'=> $request->idCarrera,
                                'idTurno'=> $request->idTurno,
                                'semestre'=> $request->semestre,
                                'monto'=> $request->monto,
                                'aplicaIns'=> $request->aplicaIns,
                                'fechaAlta'=> Carbon::now(),
                                'fechaModificacion'=>Carbon::now()
                    ]);       
        return $this->returnData('servicios',null,200);
    }

    public function destroy($idNivel,$idPeriodo,$idServicio,$idCarrera,$idTurno)
    {
        $destroy = DB::table('servicioCarrera')
                            ->where('idNivel', $idNivel  )
                            ->where('idPeriodo', $idPeriodo)
                            ->where('idServicio', $idServicio)
                            ->where('idCarrera', $idCarrera)
                            ->where('idTurno', $idTurno)
                            ->delete();

        if ($destroy == 0) 
            return $this->returnEstatus('Error en la eliminacion', 404, null);
                
        return $this->returnEstatus('Registro eliminado',200,null); 
    }

     public function update(Request $request) {
        
        $validator = Validator::make($request->all(), [
                                   'idServicio' => 'required|numeric',
                                    'idNivel' => 'required|numeric',
                                    'idPeriodo' => 'required|numeric',
                                    'monto' => 'required|numeric',
                                    'idCarrera' => 'required|numeric',
                                    'idTurno' => 'required|numeric',
                                    'semestre' => 'required|numeric',
                                    'aplicaIns' => 'required|numeric'
        ]);

       
        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $filas= DB::table('servicioCarrera')
                            ->where('idNivel', $request->idNivel  )
                            ->where('idPeriodo', $request->idPeriodo)
                            ->where('idServicio', $request->idServicio)
                            ->where('idCarrera', $request->idCarrera)
                            ->where('idTurno', $request->idTurno)
                            ->update(['monto' => $request->monto,
                            'semestre' => $request->semestre,
                            'aplicaIns' => $request->aplicaIns
                                ]);
        if ($filas > 0) 
            return $this->returnData('datos actualizados',null,200);
        else 
            return $this->returnData('No se actualizo informacion',null,200);

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
                'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/serviciosCarrerasRpt.xlsx' // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }  
    }

}
