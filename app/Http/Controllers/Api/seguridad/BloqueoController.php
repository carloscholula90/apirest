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

class BloqueoController extends Controller
{
    
   public function obtenerDatos(){
         return DB::table('bloqueos')                            
                            ->select(
                                'bloqueos.idBloqueo',
                                'bloqueos.descripcion',
                                DB::raw('CASE WHEN bloqueos.activo = 1 THEN "S" ELSE "N" END as activo')
                            )->get();
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
     
     return $pdfController->generateReport($dataArray,  // Dato
                                           [80,100,50], // Anchos de columna
                                           ['idBloqueo','descripcion','activo'], // Claves
                                           'BLOQUEOS', // Título del reporte
                                           ['ID','DESCRIPCION', 'ACTIVO'], 'L','letter',// Encabezados   ,
                                           'rptBloqueo.pdf'
     );
} 
         
   public function exportaExcel() {  
            // Ruta del archivo a almacenar en el disco público
            $path = storage_path('app/public/bloqueo_rpt.xlsx');
            $selectColumns = ['bloqueos.idBloqueo',
                              'bloqueos.descripcion',
                               DB::raw('CASE WHEN bloqueos.activo = 1 THEN "S" ELSE "N" END as activo')]; 
            $namesColumns = ['ID','SECUENCIA', 'ACTIVO']; // Seleccionar columnas específicas            
            $joins = [];

            $export = new GenericTableExportEsp('bloqueos', null, [], ['bloqueos.idBloqueo'], ['asc'], $selectColumns, $joins,$namesColumns);

            // Guardar el archivo en el disco público  
            Excel::store($export, 'bloqueo_rpt.xlsx', 'public');
        
            // Verifica si el archivo existe usando Storage de Laravel
            if (file_exists($path))  {
                return response()->json([
                    'status' => 200,  
                    'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/bloqueo_rpt.xlsx' // URL pública para descargar el archivo
                ]);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al generar el reporte '
                ]);
            }  
    }
}
