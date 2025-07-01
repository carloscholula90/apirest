<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Documento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Maatwebsite\Excel\Facades\Excel;

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
        try {
            $documentos = Documento::create([
                'idDocumento' => $newId,
                'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El documento ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el documento',400,null);
        }

        if (!$documentos) 
            return $this->returnEstatus('Error al crear el Documento',500,null); 
        return $this->returnData('$documentos',$documentos,200);   
    }

    public function show($idDocumento){
        try {
            $documentos = Documento::findOrFail($idDocumento);
            return $this->returnData('$documentos',$documentos,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Documento no encontrado',404,null); 
        }
    }
    
    public function destroy($idDocumento){
        $Documento = Documento::find($idDocumento);

        if (!$Documento) 
            return $this->returnEstatus('Documento no encontrado',404,null);             
        try {
           $Documento->delete();
            return $this->returnEstatus('Documento eliminado',200,null); 
        } catch (QueryException $e) {
        if ($e->getCode() == '23000') {
            // Este es el código de error para integridad referencial
            return $this->returnEstatus('No se puede eliminar el documento ya esta siendo utilizado',400,null); 
        } 
        }                 
     
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

    public function generaReporte()
     {
        return $this->imprimeCtl('documento',' documento ',null,null,'descripcion');
     } 

     public function exportaExcel() {
        return $this->exportaXLS('documento','idDocumento',['CLAVE', 'DESCRIPCIÓN'],'descripcion');     
    }
}
