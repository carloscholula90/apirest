<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use App\Models\tesoreria\ConfiguracionTesoreria;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;  
use Maatwebsite\Excel\Facades\Excel;

class ConfiguracionesController extends Controller{

    protected $pdfController;

    // Inyección de la clase PdfReportGenerator
    public function __construct(pdfController $pdfController)
    {
        $this->pdfController = $pdfController;
    }


    public function index(){       
        $configuracion = ConfiguracionTesoreria::all();
        return $this->returnData('configuracion',$configuracion,200);
    }   

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
                            'idNivel'=>'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

         try {
            $configuracion = ConfiguracionTesoreria::create([
                            'idNivel'=> $request->idNivel,
                            'idServicioInscripcion'=> $request->idServicioInscripcion,
                            'idServicioColegiatura'=> $request->idServicioColegiatura,
                            'idServicioNotaCargo'=> $request->idServicioNotaCargo,
                            'idServicioNotaCredito'=> $request->idServicioNotaCredito,
                            'idServicioRecargo'=> $request->idServicioRecargo,
                            'idServicioReinscripcion'=> $request->idServicioReinscripcion,
                            'fechaAlta'=> Carbon::now(),
                            'fechaModificacion'=> Carbon::now()
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('La configuracion ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar la Beca',400,null);
        }

        if (!$configuracion) 
            return $this->returnEstatus('Error al crear la configuracion',500,null); 
        return $this->returnData('configuracion',$configuracion,200);   
    }

    
    public function destroy($idNivel){
        $configuracion = ConfiguracionTesoreria::where('idNivel', $idNivel);

        if (!$configuracion) 
            return $this->returnEstatus('configuracion no encontrado',404,null);             
        
         ConfiguracionTesoreria::where('idNivel', $idNivel)->delete();  
        return $this->returnEstatus('configuracion eliminado',200,null); 
    }

    public function update(Request $request){

        $configuracion = ConfiguracionTesoreria::where('idNivel', $idNivel);
 
        if (!$configuracion) 
            return $this->returnEstatus('Beca no encontrado',404,null);             

        $configuracion->idNivel = $request->idNivel;
        $configuracion->idServicioInscripcion= $request->idServicioInscripcion;
        $configuracion->idServicioColegiatura= $request->idServicioColegiatura;
        $configuracion->idServicioNotaCargo= $request->idServicioNotaCargo;
        $configuracion->idServicioNotaCredito= $request->idServicioNotaCredito;
        $configuracion->idServicioRecargo= $request->idServicioRecargo;
        $configuracion->idServicioReinscripcion= $request->idServicioReinscripcion;
        $configuracion->fechaModificacion= Carbon::now();    
        $configuracion->save();
        return $this->returnData('Configuracion',$configuracion,200);
    }

      // Función para generar el reporte de personas
      public function generaReporte()
      {
        $configuracion = DB::table('configuracionTesoreria')
                ->select('*')
                ->get();
             
         // Si no hay personas, devolver un mensaje de error
         if ($configuracion->isEmpty())
             return $this->returnEstatus('No se encontraron datos para generar el reporte',404,null);
        
          // Convertir los datos a un formato de arreglo asociativo
        $dataArray = $configuracion->map(function ($item) {
            return (array) $item;
        })->toArray();      
         
         $headers = ['NIVEL', 'INCRIPCION','COLEGIATURA','NOTA CARGO','NOTA CREDITO','RECARGO','REINSCRIPCION'];
         $columnWidths = [50,100,100,100,100,100,100,100];   
         $keys = ['idNivel', 'idServicioInscripcion','idServicioColegiatura','idServicioNotaCargo','idServicioNotaCredito','idServicioRecargo','idServicioReinscripcion'];
        
        return $this->pdfController->generateReport($dataArray,$columnWidths,$keys , 'REPORTE DE CONFIGURACION DE SERVICIOS TESORERIA', $headers,'L','letter','rptConfiguraciones.pdf');
       
     }  

     public function exportaExcel() {
        // Ruta del archivo a almacenar en el disco público
        $path = storage_path('app/public/configuracion_rpt.xlsx');
        $namesColumns = ['NIVEL', 'INCRIPCION','COLEGIATURA','NOTA CARGO','NOTA CREDITO','RECARGO','REINSCRIPCION'];
        
        $selectColumns = ['idNivel', 'idServicioInscripcion','idServicioColegiatura','idServicioNotaCargo','idServicioNotaCredito','idServicioRecargo','idServicioReinscripcion']; // Seleccionar columnas específicas
        $export = new GenericTableExportEsp('configuracionTesoreria', 'idNivel', [], ['idNivel'], ['asc'], $selectColumns, [],$namesColumns);

        // Guardar el archivo en el disco público
        Excel::store($export, 'configuracion_rpt.xlsx', 'public');
       
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.pruebas.com.mx/storage/app/public/configuracion_rpt.xlsx' // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }
     }
}
