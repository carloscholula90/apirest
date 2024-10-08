<?php

namespace App\Http\Controllers;

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
            $data = [
                'message' => $message,
                'error' => $error,
                'status' => $status
            ];
            return response()->json($data, $status);
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
}
