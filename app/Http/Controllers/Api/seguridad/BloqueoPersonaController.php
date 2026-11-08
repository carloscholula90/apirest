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

class BloqueoPersonaController extends Controller
{
    
   public function obtenerDatos(){
         return DB::table('bloqueoPersonas as bloq')
                            ->join('bloqueos as b', 'b.idBloqueo', '=', 'bloq.idBloqueo')
                            ->join('persona as p', 'p.uid', '=', 'bloq.uid')
                            ->select(
                                'bloq.uid',
                                'secuencia',
                                'p.nombre',
                                'primerApellido',
                                'segundoApellido',
                                'b.descripcion',
                                'fechaBloqueo',
                                'fechaPermiso'
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
                                           [80,80,100,100,100,100,100,100], // Anchos de columna
                                           ['uid','secuencia','nombre','primerApellido','segundoApellido','descripcion','fechaBloqueo','fechaPermiso'], // Claves
                                           'BLOQUEOS POR PERSONAS', // Título del reporte
                                           ['UID','SECUENCIA', 'NOMBRE', 'APELLIDO PATERNO','APELLIDO MATERNO','BLOQUE','FECHA BLOQUEO','FECHA PERMISO'], 'L','letter',// Encabezados   ,
                                           'rptBloqueos.pdf'
     );
} 
         
   public function exportaExcel() {  
            // Ruta del archivo a almacenar en el disco público
            $path = storage_path('app/public/bloqueos_rpt.xlsx');
            $selectColumns = ['bloqueoPersonas.uid', 'secuencia', 'persona.nombre', 'persona.primerApellido',
                                'persona.segundoApellido',  'bloqueos.descripcion','bloqueoPersonas.fechaBloqueo', 'bloqueoPersonas.fechaPermiso']; 
            $namesColumns = ['UID','SECUENCIA', 'NOMBRE', 'APELLIDO PATERNO','APELLIDO MATERNO','BLOQUE','FECHA BLOQUEO','FECHA PERMISO']; // Seleccionar columnas específicas
            
            $joins = [[ 'table' => 'bloqueos', // Tabla a unir
                        'first' => 'bloqueos.idBloqueo', // Columna de la tabla principal
                        'second' => 'bloqueoPersonas.idBloqueo', // Columna de la tabla unida
                        'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                        ],
                        [ 'table' => 'persona', // Tabla a unir
                        'first' => 'persona.uid', // Columna de la tabla principal
                        'second' => 'bloqueoPersonas.uid', // Columna de la tabla unida
                        'type' => 'inner' // Tipo de JOIN (en este caso LEFT JOIN)
                        ]
                        ];

            $export = new GenericTableExportEsp('bloqueoPersonas', null, [], ['bloqueoPersonas.uid'], ['asc'], $selectColumns, $joins,$namesColumns);

            // Guardar el archivo en el disco público  
            Excel::store($export, 'bloqueos_rpt.xlsx', 'public');
        
            // Verifica si el archivo existe usando Storage de Laravel
            if (file_exists($path))  {
                return response()->json([
                    'status' => 200,  
                    'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/bloqueos_rpt.xlsx' // URL pública para descargar el archivo
                ]);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al generar el reporte '
                ]);
            }  
    }
}
