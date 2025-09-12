<?php

namespace App\Http\Controllers\Api\tesoreria;  
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\tesoreria\Servicio;
use App\Models\tesoreria\ServiciosPeriodo;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;

class ServiciosPeriodosController extends Controller
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
                    s.idServicio, s.descripcion AS servicio, s.tarjeta, s.efectivo,
                    sp.monto,s.cargoAutomatico
            FROM servicio s
            INNER JOIN serviciosPeriodo sp ON sp.idServicio = s.idServicio
            INNER JOIN nivel n ON n.idNivel = sp.idNivel
            INNER JOIN periodo p ON p.idNivel = sp.idNivel AND p.idPeriodo = sp.idPeriodo
            WHERE (p.activo = 1 OR p.inscripciones = 1)
            ORDER BY n.idNivel, p.idPeriodo, s.descripcion");

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
        'efectivo' => $row->efectivo,
        'tarjeta' => $row->tarjeta,
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
                                    'descripcion' => 'required|max:255',
                                    'efectivo' => 'required|numeric',
                                    'tarjeta' => 'required|numeric',
                                    'cargoAutomatico' => 'required|numeric',
                                    'idNivel' => 'required|numeric',
                                    'idPeriodo' => 'required|numeric',
                                    'monto' => 'required|numeric'
        ]);   
       
        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors());
        if(isset($request->idServicio)) {
            //El servicio ya existe entonces solo actualiza
            $idServicio =0;       
            $idServicio = $request->idServicio;
            $servicio = Servicio::find($request->idServicio);     
            $servicio->efectivo = $request->efectivo;
            $servicio->tarjeta = $request->tarjeta;
            $servicio->cargoAutomatico = $request->cargoAutomatico;       
            $servicio->save(); 
        }
        else{
            $maxId = Servicio::max('idServicio');
            $idServicio = $maxId ? $maxId + 1 : 1;
            $servicio = Servicio::create([
                                'idServicio' => $idServicio,
                                'descripcion' => strtoupper(trim($request->descripcion)),
                                'efectivo' => $request->efectivo,
                                'tarjeta' => $request->tarjeta,
                                'cargoAutomatico' => $request->cargoAutomatico
                    ]);
            }

             $servicioC = ServiciosPeriodo::create([
                                'idNivel'=> $request->idNivel,
                                'idPeriodo'=> $request->idPeriodo,
                                'idServicio' => $request->idServicio,
                                'monto'=> $request->monto
                    ]);       
        return $this->returnData('servicios',null,200);
    }

    public function destroy($idNivel,$idPeriodo,$idServicio)
    {
        $destroy = DB::table('serviciosPeriodo')
                            ->where('idNivel', $idNivel  )
                            ->where('idPeriodo', $idPeriodo)
                            ->where('idServicio', $idServicio)
                            ->delete();

        if ($destroy == 0) 
            return $this->returnEstatus('Error en la eliminacion', 404, null);
                
        return $this->returnEstatus('Registro eliminado',200,null); 
    }

     public function update(Request $request) {
        
        $validator = Validator::make($request->all(), [
                                    'idNivel' => 'required|numeric',
                                    'idPeriodo' => 'required|numeric',
                                    'idServicio' => 'required|numeric',
                                    'monto' => 'required|numeric'
        ]);

        $idServicio =0;
        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        DB::table('serviciosPeriodo')
                ->where('idNivel', $request->idNivel)
                ->where('idPeriodo', $request->idPeriodo)
                ->where('idServicio', $request->idServicio)
                ->update(['monto' => $request->monto]);

        return $this->returnData('servicios',null,200);
    }    

       // Función para generar el reporte de personas
      public function generaReporte()
      {
        $datos = $this->getDatos();  
     
         // Si no hay personas, devolver un mensaje de error
         if ($datos->isEmpty())
             return $this->returnEstatus('No se encontraron datos para generar el reporte',404,null);
         
         $headers = ['PERIODO','NIVEL','SERVICIO','MONTO'];
         $columnWidths = [120,120,350,100];   
         $keys = ['periodo','nivel','servicio','monto'];
        
         $datosArray = $datos->map(function ($item) {
            return (array) $item;
         })->toArray();  
     
         return $this->pdfController->generateReport($datosArray,$columnWidths,$keys , 'REPORTE DE SERVICIOS POR NIVEL', $headers,'L','letter','rptReporteServicios.pdf');
     }  

      public function getDatos(){
         $resultado = DB::table('serviciosPeriodo as sp')
                ->select(
                    'p.descripcion as periodo',
                    'n.descripcion as nivel',
                    's.descripcion as servicio',                  
                    DB::raw("CONCAT('$', FORMAT(sp.monto, 2)) as monto")
                )
                ->join('servicio as s', 's.idServicio', '=', 'sp.idServicio')
                ->join('periodo as p', function ($join) {
                    $join->on('p.idNivel', '=', 'sp.idNivel')
                        ->on('p.idPeriodo', '=', 'sp.idPeriodo')
                        ->where(function ($query) {
                            $query->where('p.activo', 1)
                                ->orWhere('p.inscripciones', 1);
                        });
                })
                ->join('nivel as n', 'n.idNivel', '=', 'sp.idNivel')
                ->orderByDesc('n.idNivel')
                ->orderByDesc('p.idPeriodo')
                ->orderBy('s.idServicio')
                ->get();

        return $resultado;
    }
    public function exportaExcel() {  
        // Ruta del archivo a almacenar en el disco público
        $path = storage_path('app/public/servicios_rpt.xlsx');
        $selectColumns = ['periodo.descripcion as periodo', 'nivel.descripcion as nivel',
                    'servicio.descripcion as servicio',                  
                    DB::raw("CONCAT('$', FORMAT(serviciosPeriodo.monto, 2)) as monto")]; // Seleccionar columnas específicas
        $namesColumns = ['PERIODO','NIVEL','SERVICIO','MONTO'];
        
        $joins = [[ 'table' => 'servicio', // Tabla a unir
                    'first' => 'servicio.idServicio', // Columna de la tabla principal
                    'second' => 'serviciosPeriodo.idServicio', // Columna de la tabla unida
                    'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ],
                [ 'table' => 'nivel', // Tabla a unir
                  'first' => 'nivel.idNivel', // Columna de la tabla principal
                  'second' => 'serviciosPeriodo.idNivel', // Columna de la tabla unida
                  'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ],
                [ 'table' => 'periodo', // Tabla a unir
                  'first' => 'periodo.idNivel','periodo.idPeriodo' ,// Columna de la tabla principal
                  'second' => 'serviciosPeriodo.idNivel','serviciosPeriodo.idPeriodo', // Columna de la tabla unida
                  'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                ]
                ];

        $export = new GenericTableExportEsp('serviciosPeriodo', 'descripcion', [], ['nivel.idNivel','periodo.idPeriodo'], ['asc','desc'], $selectColumns, $joins,$namesColumns);

        // Guardar el archivo en el disco público
        Excel::store($export, 'serviciosPeriodosRpt.xlsx', 'public');
       
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {  
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/serviciosPeriodosRpt.xlsx' // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }  
    }
}