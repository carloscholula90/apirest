<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Edificio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EdificioController extends Controller{

    public function index(){       
        $edificios = Edificio::all();
        return $this->returnData('edificios',$edificios,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255',
                    'direccion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Edificio::max('idEdificio');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $edificios = Edificio::create([
                            'idEdificio' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion)),
                            'direccion' => strtoupper(trim($request->direccion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El Edificio ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el Edificio',400,null);
        }

        if (!$edificios) 
            return $this->returnEstatus('Error al crear el Edificio',500,null); 
        return $this->returnData('$edificios',$edificios,201);   
    }

    public function show($idEdificio){
        try {
            $edificios = Edificio::findOrFail($idEdificio);
            return $this->returnData('$edificios',$edificios,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Edificio no encontrado',404,null); 
        }
    }
    
    public function destroy($idEdificio){
        $edificio = Edificio::find($idEdificio);

        if (!$edificio) 
            return $this->returnEstatus('Edificio no encontrado',404,null);             
        
            $edificio->delete();
        return $this->returnEstatus('Edificio eliminado',200,null); 
    }

    public function update(Request $request, $idEdificio){

        $edificio = Edificio::find($idEdificio);
        
        if (!$edificio) 
            return $this->returnEstatus('Edificio no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idEdificio' => 'required|numeric|max:255',
                                    'direccion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $edificio->idEdificio = $request->idEdificio;
        $edificio->descripcion = strtoupper(trim($request->descripcion));
        $edificio->direccion = strtoupper(trim($request->direccion));
        $edificio->save();
        return $this->returnData('edificios',$edificio,200);
    }  

    public function updatePartial(Request $request, $idEdificio){

        $edificio = Edificio::find($idEdificio);
        
        if (!$edificio) 
            return $this->returnEstatus('Edificio no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'descripcion' => 'required|max:255',
                                    'direccion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idEdificio')) 
            $edificio->idEdificio = $request->idEdificio;        

        if ($request->has('descripcion')) 
            $edificio->descripcion = strtoupper(trim($request->descripcion));  
        
        if ($request->has('direccion')) 
            $edificio->direccion = strtoupper(trim($request->direccion));

        $edificio->save();
        return $this->returnEstatus('Edificio actualizado',200,null);    
    }
     
    public function generaReporte(){
       return $this->imprimeCtl('edificio',' edificios ',['CLAVE', 'DESCRIPCIÓN','DESCRIPCION'],[100,200,300],'descripcion');
    } 

    public function exportaExcel() {
       return $this->exportaXLS('edificio','idEdificio',['CLAVE', 'DESCRIPCIÓN','DESCRIPCION'],'descripcion');     
   }   
}
