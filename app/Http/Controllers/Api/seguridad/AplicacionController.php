<?php

namespace App\Http\Controllers\Api\seguridad;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\seguridad\Aplicacion;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;

class AplicacionController extends Controller
{
    public function index()
    {
        $aplicacion = Aplicacion::all();
        return $this->returnData('aplicacion',$aplicacion,200);        
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                            'descripcion' =>'required|max:255',
                            'activo' => 'required|numeric|max:255',
                            'idModulo' => 'required|numeric|max:255'

        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxIdAplicacion = Aplicacion::max('idAplicacion');
        $newIdAplicacion = $maxIdAplicacion ? $maxIdAplicacion + 1 : 1;
      
        $aplicacion = Aplicacion::create([
                                'idAplicacion' =>  $newIdAplicacion,
                                'descripcion' => $request->descripcion,
                                'activo' => $request->activo,
                                'idModulo' => $request->idModulo
        ]);

        if (!$aplicacion) 
            return $this->returnEstatus('Error al crear la aplicacion',500,null);

        $aplicacion = Aplicacion::findOrFail($newIdAplicacion);        
        return $this->returnData('aplicacion',$aplicacion,200);
    }

    public function show($id)
    {
        $aplicacion = Aplicacion::find($id);
        if (!$aplicacion)
            return $this->returnEstatus('Aplicacion no encontrada',404,null);
        return $this->returnData('aplicacion',$aplicacion,200);
    }

    public function destroy($id)
    {
        $aplicacion = Aplicacion::find($id);

        if (!$aplicacion) 
            return $this->returnEstatus('Aplicacion no encontrada',404,null);
        
        $aplicacion->delete();
        return $this->returnEstatus('Aplicacion eliminada',200,null);
    }

    public function update(Request $request, $id)
    {
        $aplicacion = Aplicacion::find($id);

        if (!$aplicacion) 
            return $this->returnEstatus('Aplicacion no encontrada',404,null);

        $validator = Validator::make($request->all(), [
                                    'idAplicacion' => $request->idAplicacion,
                                    'descripcion' => $request->descripcion,
                                    'activo' => $request->activo,
                                    'idModulo' => $request->idModulo
        ]);

        if ($validator->fails()) 
        return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $aplicacion->idAplicacion = $request->idAplicacion;
        $aplicacion->descripcion = strtoupper(trim($request->descripcion));
        $aplicacion->activo = strtoupper(trim($request->activo));
        $aplicacion->idModulo = $request->idModulo;

        $aplicacion->save();
        return $this->returnEstatus('Aplicacion actualizada',200,null);
    }

    public function updatePartial(Request $request, $idAplicacion)
    {
        $aplicacion = Aplicacion::find($idAplicacion);

        if (!$aplicacion) 
            return $this->returnEstatus('Aplicacion no encontrada',404,null);

        $aplicacion->idAplicacion = $idAplicacion;
        

        if ($request->has('descripcion')) 
            $aplicacion->descripcion = strtoupper(trim($request->descripcion));
        
        if ($request->has('activo')) 
            $aplicacion->activo = strtoupper(trim($request->activo));
        

        if ($request->has('idModulo')) 
            $aplicacion->idModulo = $request->idModulo;
        

        $aplicacion->save();
        return $this->returnEstatus('Aplicacion actualizada',200,null);
    }
    
   public function obtenerDatos(){
         return DB::table('aplicaciones as apl')
                        ->select(
                                'mod.descripcion as modDescripcion',
                                'apl.idAplicacion',
                                'apl.descripcion',
                                DB::raw('CASE WHEN apl.activo = 1 THEN "S" ELSE "N" END as activo'))
                                        ->join('modulos as mod', 'mod.idModulo', '=', 'apl.idModulo')
                                        ->orderBy('apl.descripcion', 'asc')
                                        ->get();
}    

public function generaReporte(){
    $data = $this->obtenerDatos();

    if(empty($data)){
        return response()->json([
            'status' => 500,
            'message' => 'No hay datos para generar el reporte'
        ]);
    }

     // Convertir los datos a un formato de arreglo asociativo
     $dataArray = $data->map(function ($item) {
        return (array) $item;
    })->toArray();

     // Generar el PDF
     $pdfController = new pdfController();
     
     return $pdfController->generateReport($dataArray,  // Datos
                                           [100,100,300,100], // Anchos de columna
                                           ['modDescripcion','idAplicacion','descripcion','activo'], // Claves
                                           'CATÁLOGO DE APLICACIONES', // Título del reporte
                                           ['MODULO','CLAVE', 'DESCRIPCION', 'ACTIVO'], 'L','letter',// Encabezados   ,
                                           'rptAplicaciones.pdf'
     );
} 
         
   public function exportaExcel() {  
            // Ruta del archivo a almacenar en el disco público
            $path = storage_path('app/public/aplicaciones_rpt.xlsx');
            $selectColumns = ['modulos.descripcion as descrip','aplicaciones.idAplicacion', 'aplicaciones.descripcion', 'activo']; 
            $namesColumns = ['MODULO','CLAVE', 'DESCRIPCION', 'ACTIVO']; // Seleccionar columnas específicas
            
            $joins = [[ 'table' => 'modulos', // Tabla a unir
                        'first' => 'aplicaciones.idModulo', // Columna de la tabla principal
                        'second' => 'modulos.idModulo', // Columna de la tabla unida
                        'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                    ]];

            $export = new GenericTableExportEsp('aplicaciones', 'descripcion', [], 'aplicaciones.descripcion', 'asc', $selectColumns, $joins,$namesColumns);

            // Guardar el archivo en el disco público  
            Excel::store($export, 'aplicaciones_rpt.xlsx', 'public');
        
            // Verifica si el archivo existe usando Storage de Laravel
            if (file_exists($path))  {
                return response()->json([
                    'status' => 200,  
                    'message' => 'https://reportes.siaweb.com.mx/storage/carrera_rpt.xlsx' // URL pública para descargar el archivo
                ]);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al generar el reporte '
                ]);
            }  
    }
}
