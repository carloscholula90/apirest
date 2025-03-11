<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Alergia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;

class AlergiaController extends Controller{
    
    public function store(Request $request){

        $validator = Validator::make($request->all(), [
                    'uid' => 'required|numeric|max:11',                    
                    'alergia' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Alergia::where('uid', $request->uid)  
                        ->max('consecutivo');  

        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $alergias = Alergia::create([
                            'uid' => $request->uid,
                            'consecutivo' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                return $this->returnEstatus('La alergia ya se encuentra dado de alta',400,null);                
            return $this->returnEstatus('Error al insertar la alergia',400,null);
        }

        if (!$alergias) 
            return $this->returnEstatus('Error al crear la alergia',500,null); 
        return $this->returnData('alergias',$alergias,201);   
    }

    public function show($uid){
        try {
            $alergias = Alergia::select('uid','consecutivo','alergia')
                            ->where('uid',$uid)
                            ->get();      
            if ($alergias) 
                return $this->returnData('alergias',$alergias,200);     
            else return $this->returnEstatus('Registro no encontrado',404,null); 
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Alergia no encontrado',404,null); 
        }  
    }
    
    public function destroy($uid,$consecutivo){
        $alergias = Alergia::where('uid', $uid)
                            ->where('consecutivo',$consecutivo); 
        $alergias->delete();

        if (!$alergias) 
            return $this->returnEstatus('Alergia no encontrada',404,null);  
        return $this->returnEstatus('Alergia eliminada',200,null); 
    }

    public function update(Request $request, $uid,$consecutivo){

        $alergias = Alergia::where('uid', $uid)
                            ->where('consecutivo',$consecutivo); 
        
        if (!$alergias) 
            return $this->returnEstatus('Alergia no encontrado',404,null);             

        $validator = Validator::make($request->all(), [                   
                     'alergia' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $alergias->alergia = strtoupper(trim($request->alergia));
        $alergias->save();
        return $this->returnData('alergias',$alergias,200);
    }
     
    public function obtenerDatos($uid){
            return Alergia::select('uid','consecutivo','alergia')
                            ->where('uid',$uid)
                            ->get();     
    }

    public function generaReporte(Request $request){
        $data = $this->obtenerDatos($request->uid);
      
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
                                               [100,100,300], // Anchos de columna
                                               ['uid','consecutivo','alergia'], // Claves
                                               'ALERGIAS', // Título del reporte
                                               ['UID','CONSECUTIVO','ALERGIA'], 'L','letter',// Encabezados   ,
                                               'rptAlergias.pdf'    
         );
    } 
}
