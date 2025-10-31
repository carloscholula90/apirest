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

class ServiciosController extends Controller
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
    public function index(){
        return DB::select("
            SELECT s.idServicio, s.descripcion, CASE WHEN s.tarjeta = 1 THEN 'S' ELSE 'N' END tarjeta, 
                   CASE WHEN s.efectivo = 1 THEN 'S' ELSE 'N' END efectivo,
                   CASE WHEN s.cargoAutomatico = 1 THEN 'S' ELSE 'N' END cargoAutomatico
            FROM servicio s
            ORDER BY s.idServicio");
    }

 public function store(Request $request) {
        
        $validator = Validator::make($request->all(), [
                                    'descripcion' => 'required|max:255',
                                    'efectivo' => 'required|numeric',
                                    'tarjeta' => 'required|numeric',
                                    'cargoAutomatico' => 'required|numeric'
        ]);   
       
        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors());
        
            $maxId = Servicio::max('idServicio');
            $idServicio = $maxId ? $maxId + 1 : 1;
            $servicio = Servicio::create([
                                'idServicio' => $idServicio,
                                'descripcion' => strtoupper(trim($request->descripcion)),
                                'efectivo' => $request->efectivo,
                                'tarjeta' => $request->tarjeta,
                                'cargoAutomatico' => $request->cargoAutomatico
                    ]);
            
        return $this->returnData('servicios',null,200);
    }

    public function destroy($idServicio)
    {
        $destroy = DB::table('servicio')
                            ->where('idServicio', $idServicio)
                            ->delete();

        if ($destroy == 0) 
            return $this->returnEstatus('Error en la eliminacion', 404, null);
                
        return $this->returnEstatus('Registro eliminado',200,null); 
    }

     public function update(Request $request) {
        
        $validator = Validator::make($request->all(), [
                                    'efectivo' => 'required|numeric',
                                    'tarjeta' => 'required|numeric',
                                    'descripcion' => 'required|max:255',
                                    'idServicio' => 'required|numeric',
                                    'cargoAutomatico' => 'required|numeric'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        DB::table('serviciosPeriodo')
                ->where('idServicio', $request->idServicio)
                ->update(['efectivo' => $request->efectivo,
                          'tarjeta' => $request->tarjeta,
                          'descripcion' => $request->descripcion,
                          'cargoAutomatico' => $request->cargoAutomatico]);

        return $this->returnData('servicios',null,200);
    }    

       // Función para generar el reporte de personas
      public function generaReporte(){
        
         $datos = DB::table('servicio as s')
        ->select([
            's.idServicio',
            's.descripcion',
            DB::raw("CASE WHEN s.tarjeta = 1 THEN 'S' ELSE 'N' END AS tarjeta"),
            DB::raw("CASE WHEN s.efectivo = 1 THEN 'S' ELSE 'N' END AS efectivo"),
            DB::raw("CASE WHEN s.cargoAutomatico = 1 THEN 'S' ELSE 'N' END AS cargoAutomatico"),
        ])
        ->orderBy('s.descripcion')
        ->get(); // <- devuelve una Collection
     
         // Si no hay personas, devolver un mensaje de error
         if ($datos->isEmpty())
             return $this->returnEstatus('No se encontraron datos para generar el reporte',404,null);
         
         $headers = ['ID SERVICIO','DESCRIPCION','EFECTIVO','TARJETA','CARGA AUTOMATICO'];
         $columnWidths = [100,200,100,100,100];   
         $keys = ['idServicio','descripcion','efectivo','tarjeta','cargoAutomatico'];
        
         $datosArray = $datos->map(function ($item) {
            return (array) $item;
         })->toArray(); 
         return $this->pdfController->generateReport($datosArray,$columnWidths,$keys , 'REPORTE DE SERVICIOS', $headers,'L','letter','rptReporteServicios.pdf');
    } 
     
    public function exportaExcel() {  
        // Ruta del archivo a almacenar en el disco público
        $path = storage_path('app/public/serviciosRpt.xlsx');
        $selectColumns = ['idServicio', 'descripcion',
                    DB::raw('CASE WHEN tarjeta = 1 THEN "S" ELSE "N" END tarjeta'),
                    DB::raw('CASE WHEN efectivo = 1 THEN "S" ELSE "N" END as efectivo'),
                    DB::raw('CASE WHEN cargoAutomatico = 1 THEN "S" ELSE "N" END as cargoAutomatico')]; // Seleccionar columnas específicas
        $namesColumns = ['ID SERVICIO','DESCRIPCION','EFECTIVO','TARJETA','CARGA AUTOMATICO'];
        $export = new GenericTableExportEsp('servicio', 'descripcion', [], ['idServicio'], ['asc'], $selectColumns, [],$namesColumns);

        // Guardar el archivo en el disco público
        Excel::store($export, 'serviciosRpt.xlsx', 'public');
       
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {  
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/serviciosRpt.xlsx' // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }  
    }
}