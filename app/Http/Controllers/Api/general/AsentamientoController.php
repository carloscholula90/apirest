<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Asentamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AsentamientoController extends Controller{

    public function index(){       
        $asentamientos = Asentamiento::all();
        return $this->returnData('asentamientos',$asentamientos,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Asentamiento::max('idAsentamiento');  
        $newId = $maxId ? $maxId + 1 : 1; 
        $asentamiento = Asentamiento::create([
                        'idAsentamiento' => $newId,
                        'descripcion' => strtoupper(trim($request->descripcion))
        ]);

        if (!$asentamiento) 
            return $this->returnEstatus('Error al crear el asentamiento',500,null); 
        return $this->returnData('asentamiento',$asentamiento,201);   
    }

    public function show($idAsentamiento){
        try {
            $asentamiento = Asentamiento::findOrFail($idAsentamiento);
            return $this->returnData('asentamiento',$asentamiento,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Asentamiento no encontrado',404,null); 
        }
    }
    
    public function destroy($idAsentamiento){
        $asentamiento = Asentamiento::find($idAsentamiento);

        if (!$asentamiento) 
            return $this->returnEstatus('Asentamiento no encontrado',404,null);             
        
            $asentamiento->delete();
        return $this->returnEstatus('Asentamiento eliminado',200,null); 
    }

    public function update(Request $request, $idAsentamiento){

        $asentamiento = Asentamiento::find($idAsentamiento);
        
        if (!$asentamiento) 
            return $this->returnEstatus('Asentamiento no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                    'idAsentamiento' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $asentamiento->idAsentamiento = $request->idAsentamiento;
        $asentamiento->descripcion = strtoupper(trim($request->descripcion));
        $asentamiento->save();
        return $this->returnData('asentamiento',$asentamiento,200);
    }

    public function updatePartial(Request $request, $idAsentamiento){

        $asentamiento = Asentamiento::find($idAsentamiento);
        
        if (!$asentamiento) 
            return $this->returnEstatus('Asentamiento no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idAsentamiento' => 'required|numeric|max:255',
                                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idAsentamiento')) 
            $asentamiento->idAsentamiento = $request->idAsentamiento;        

        if ($request->has('descripcion')) 
            $asentamiento->descripcion = strtoupper(trim($request->descripcion));        

        $asentamiento->save();
        return $this->returnEstatus('Asentamiento actualizado',200,null);    
    }

    public function exportaExcel() {
        return $this->exportaXLS('asentamiento','idAsentamiento',['CLAVE', 'DESCRIPCIÓN']);     
    }

    public function generaReporte()
    {
       return $this->imprimeCtl('asentamiento','idAsentamiento',null,null,'descripcion');
   }
}
