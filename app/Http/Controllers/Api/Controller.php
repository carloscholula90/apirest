<?php

namespace App\Http\Controllers\Api\;  
use App\Http\Controllers\Controller;
use App\Models\\;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class Controller extends Controller{

    public function index(){       
        $ = ::all();
        return $this->returnData('',$,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = ::max('id');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $ = ::create([
                            'id' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El  ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el ',400,null);
        }

        if (!$) 
            return $this->returnEstatus('Error al crear el ',500,null); 
        return $this->returnData('$',$,201);   
    }

    public function show($id){
        try {
            $ = ::findOrFail($id);
            return $this->returnData('$',$,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus(' no encontrado',404,null); 
        }
    }
    
    public function destroy($id){
        $ = ::find($id);

        if (!$) 
            return $this->returnEstatus(' no encontrado',404,null);             
        
            $->delete();
        return $this->returnEstatus(' eliminado',200,null); 
    }

    public function update(Request $request, $id){

        $ = ::find($id);
        
        if (!$) 
            return $this->returnEstatus(' no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                    'id' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $->id = $request->id;
        $->descripcion = strtoupper(trim($request->descripcion));
        $->save();
        return $this->returnData('',$,200);
    }

    public function updatePartial(Request $request, $id){

        $ = ::find($id);
        
        if (!$) 
            return $this->returnEstatus(' no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'id' => 'required|numeric|max:255',
                                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('id')) 
            $->id = $request->id;        

        if ($request->has('descripcion')) 
            $->descripcion = strtoupper(trim($request->descripcion));        

        $->save();
        return $this->returnEstatus(' actualizado',200,null);    
    }

     
    public function generaReporte()
    {
       return $this->imprimeCtl('notas',' notas ',null,null,'descripcion');
    } 

    public function exportaExcel() {
       return $this->exportaXLS('{tabla}','id',['CLAVE', 'DESCRIPCIÓN'],'descripcion');     
   }   
}
