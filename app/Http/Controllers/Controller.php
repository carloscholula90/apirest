<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 

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
    public function imprimeCtl($tableName, $name)
    {
        // Verificar si la tabla existe
        if (!Schema::hasTable($tableName)) {
            return $this->returnEstatus('La tabla no existe.', 404, null);
        }

        // Obtener las columnas de la tabla
        $columns = Schema::getColumnListing($tableName);

        // Consultar los datos de la tabla
        $data = DB::table($tableName)->get();

        // Convertir los datos a un formato de arreglo asociativo
        $dataArray = $data->map(function ($item) {
            return (array) $item;
        })->toArray();

        // Definir los encabezados y anchos de columna para el PDF
        $headers = ['CLAVE','DESCRIPCIÓN'];
        $columnWidths = [100,300]; // Ajusta los anchos según sea necesario

        // Generar el PDF
        $pdfController = new pdfController();
        return $pdfController->generateReport(
            $dataArray,  // Datos
            $columnWidths, // Anchos de columna
            $columns, // Claves
            'CATÁLOGO DE ' . $name, // Título del reporte
            $headers // Encabezados   
        );
    }

}
