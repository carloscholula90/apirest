<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use App\Models\tesoreria\Nota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class NotaController extends Controller{

    public function index(){       
        $notas = Nota::all();
        return $this->returnData('notas',$notas,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Nota::max('idNota');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $notas = Nota::create([
                            'idNota' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('La nota ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar la nota',400,null);
        }

        if (!$notas) 
            return $this->returnEstatus('Error al crear la nota',500,null); 
        return $this->returnData('$notas',$notas,200);   
    }

    public function show($idNota){
        try {
            $notas = Nota::findOrFail($idNota);
            return $this->returnData('$notas',$notas,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Nota no encontrada',404,null); 
        }
    }
    
    public function destroy($idNota){
        $Nota = Nota::find($idNota);

        if (!$Nota) 
            return $this->returnEstatus('Nota no encontrada',404,null);             
        
            $Nota->delete();
        return $this->returnEstatus('Nota eliminada',200,null); 
    }

    public function update(Request $request, $idNota){

        $Nota = Nota::find($idNota);
        
        if (!$Nota) 
            return $this->returnEstatus('Nota no encontrada',404,null);             

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $Nota->idNota = $request->idNota;
        $Nota->descripcion = strtoupper(trim($request->descripcion));
        $Nota->save();
        return $this->returnData('Nota',$Nota,200);
    }

     public function generaReporte()
     {
       return $this->imprimeCtl('notas',' notas ',null,null,'descripcion');
     } 

     public function exportaExcel() {
        return $this->exportaXLS('notas','idNota',['CLAVE', 'DESCRIPCIÓN'],'descripcion');     
    }

}
