<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExport;   
use Illuminate\Support\Facades\Log;

abstract class Controller
{   
 
 /**  
 * Devuelve una respuesta JSON con un mensaje de estado, código y posibles errores.
 *
 * @param string $message Mensaje que describe el estado de la operación.
 * @param int $status Código de estado HTTP (ej. 200 para éxito, 400 para error de validación).
 * @param mixed $error Detalles adicionales del error (puede ser una cadena, un arreglo u objeto).
 *
 * @return \Illuminate\Http\JsonResponse Respuesta JSON con el mensaje, error y código de estado.*/

  public function returnEstatus($message,$status,$error){
        if($error!=null){
            $data = [
                'message' => $message,
                'error' => $error,
                'status' => $status
            ];
            return response()->json($data, $status);
        }else{
            $data = [
                'message' => $message,
                'status' => $status
            ];
            return response()->json($data, $status);
        }
    }

    /**
     * Devuelve una respuesta JSON con datos y un código de estado.
     *
     * @param string $nameArray Nombre de la clave que contendrá los datos en la respuesta JSON.
     * @param mixed $data Los datos que se incluirán en la respuesta (puede ser un arreglo, objeto, etc.).
     * @param int $status Código de estado HTTP (ej. 200 para éxito, 404 para no encontrado).
     *
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con los datos y el código de estado.
     */
    public function returnData($nameArray,$data,$status){
        $data = [
             $nameArray => $data,
            'status' => $status
        ];
        return response()->json($data, $status);
    }

    /**
     * 
     */
    public function imprimeCtl($tableName, $name,$headers=null,$columnWidths=null,$order=null)
    {
        // Verificar si la tabla existe
        if (!Schema::hasTable($tableName)) {
            return $this->returnEstatus('La tabla no existe.', 404, null);
        }
     
        $columns = Schema::getColumnListing($tableName);

        // Consultar los datos de la tabla y ordenar por un campo si se pasa el parámetro
        $query = DB::table($tableName);

        if ($order) 
            $query = $query->orderBy($order); // Ordenar por el campo especificado
            
        $data = $query->get();
        // Consultar los datos de la tabla
        $data = DB::table($tableName)->get();

        if(empty( $data)){
            return response()->json([
                'status' => 500,
                'message' => 'No hay datos para generar el reporte
                '
            ]);
        }
     
        // Convertir los datos a un formato de arreglo asociativo
        $dataArray = $data->map(function ($item) {
            return (array) $item;
        })->toArray();

        // Definir los encabezados y anchos de columna para el PDF
        if($headers==null) {
            $headers = ['CLAVE','DESCRIPCIÓN'];
            $columnWidths = [100,300]; // Ajusta los anchos según sea necesario
        }

        // Generar el PDF
        $pdfController = new pdfController();
        return $pdfController->generateReport(
            $dataArray,  // Datos
            $columnWidths, // Anchos de columna
            $columns, // Claves
            'CATÁLOGO DE ' . $name, // Título del reporte
            $headers, 'L','letter',// Encabezados   ,
            'rpt'.$tableName.mt_rand(1, 100).'.pdf'
        );
    }

    /**
     * Exporta los datos de una tabla a Excel.
     *
     * @param string $tableName Nombre de la tabla.
     * @return Response
     */
    public function exportaXLS($tableName,$nameId,$headers = [],$order=null)
    {   
        $path = storage_path('app/public/' . $tableName . '_rpt.xlsx');
        Excel::store(new GenericTableExport($tableName, $nameId, $headers,$order), '' . $tableName . '_rpt.xlsx', 'public');
       
        if (file_exists($path)) {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/'. $tableName . '_rpt.xlsx' // Puedes devolver la ruta para fines de depuración
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }  
    }  
}
