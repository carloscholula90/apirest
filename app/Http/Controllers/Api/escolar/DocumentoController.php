<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Documento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DocumentoController extends Controller{

    public function index(){       
        $documentos = Documento::all();
        return $this->returnData('documentos',$documentos,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Documento::max('idDocumento');  
        $newId = $maxId ? $maxId + 1 : 1; 
        $documentos = Documento::create([
                        'idDocumento' => $newId,
                        'descripcion' => strtoupper(trim($request->descripcion))
        ]);

        if (!$Documento) 
            return $this->returnEstatus('Error al crear el Documento',500,null); 
        return $this->returnData('$documentos',$$documentos,201);   
    }

    public function show($idDocumento){
        try {
            $$documentos = Documento::findOrFail($idDocumento);
            return $this->returnData('$documentos',$$documentos,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Documento no encontrado',404,null); 
        }
    }
    
    public function destroy($idDocumento){
        $Documento = Documento::find($idDocumento);

        if (!$Documento) 
            return $this->returnEstatus('Documento no encontrado',404,null);             
        
            $Documento->delete();
        return $this->returnEstatus('Documento eliminado',200,null); 
    }

    public function update(Request $request, $idDocumento){

        $Documento = Documento::find($idDocumento);
        
        if (!$Documento) 
            return $this->returnEstatus('Documento no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                    'idDocumento' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $Documento->idDocumento = $request->idDocumento;
        $Documento->descripcion = strtoupper(trim($request->descripcion));
        $Documento->save();
        return $this->returnData('Documento',$Documento,200);
    }

    public function updatePartial(Request $request, $idDocumento){

        $Documento = Documento::find($idDocumento);
        
        if (!$Documento) 
            return $this->returnEstatus('Documento no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idDocumento' => 'required|numeric|max:255',
                                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idDocumento')) 
            $Documento->idDocumento = $request->idDocumento;        

        if ($request->has('descripcion')) 
            $Documento->descripcion = strtoupper(trim($request->descripcion));        

        $Documento->save();
        return $this->returnEstatus('Documento actualizado',200,null);    
    }
}
