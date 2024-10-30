<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use App\Models\tesoreria\Impuesto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ImpuestoController extends Controller{

    public function index(){       
        $impuestos = Impuesto::all();
        return $this->returnData('impuestos',$impuestos,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Impuesto::max('idImpuesto');  
        $newId = $maxId ? $maxId + 1 : 1; 
        $impuestos = Impuesto::create([
                        'idImpuesto' => $newId,
                        'descripcion' => strtoupper(trim($request->descripcion))
        ]);

        if (!$Impuesto) 
            return $this->returnEstatus('Error al crear el Impuesto',500,null); 
        return $this->returnData('$impuestos',$$impuestos,201);   
    }

    public function show($idImpuesto){
        try {
            $$impuestos = Impuesto::findOrFail($idImpuesto);
            return $this->returnData('$impuestos',$$impuestos,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Impuesto no encontrado',404,null); 
        }
    }
    
    public function destroy($idImpuesto){
        $Impuesto = Impuesto::find($idImpuesto);

        if (!$Impuesto) 
            return $this->returnEstatus('Impuesto no encontrado',404,null);             
        
            $Impuesto->delete();
        return $this->returnEstatus('Impuesto eliminado',200,null); 
    }

    public function update(Request $request, $idImpuesto){

        $Impuesto = Impuesto::find($idImpuesto);
        
        if (!$Impuesto) 
            return $this->returnEstatus('Impuesto no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                    'idImpuesto' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $Impuesto->idImpuesto = $request->idImpuesto;
        $Impuesto->descripcion = strtoupper(trim($request->descripcion));
        $Impuesto->save();
        return $this->returnData('Impuesto',$Impuesto,200);
    }

    public function updatePartial(Request $request, $idImpuesto){

        $Impuesto = Impuesto::find($idImpuesto);
        
        if (!$Impuesto) 
            return $this->returnEstatus('Impuesto no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idImpuesto' => 'required|numeric|max:255',
                                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idImpuesto')) 
            $Impuesto->idImpuesto = $request->idImpuesto;        

        if ($request->has('descripcion')) 
            $Impuesto->descripcion = strtoupper(trim($request->descripcion));        

        $Impuesto->save();
        return $this->returnEstatus('Impuesto actualizado',200,null);    
    }
}
