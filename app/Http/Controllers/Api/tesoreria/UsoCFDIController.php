<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use App\Models\tesoreria\UsoCFDI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UsoCFDIController extends Controller{

    public function index(){       
        $usoscfdi = UsoCFDI::all();
        return $this->returnData('usoscfdi',$usoscfdi,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255',
                    'fisica' => 'required|max:255',
                    'moral' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = UsoCFDI::max('idUsoCFDI');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $usoscfdi = UsoCFDI::create([
                            'idUsoCFDI' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion)),
                            'fisica' => strtoupper(trim($request->fisica)),
                            'moral' => strtoupper(trim($request->moral))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El UsoCFDI ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el UsoCFDI',400,null);
        }

        if (!$usoscfdi) 
            return $this->returnEstatus('Error al crear el UsoCFDI',500,null); 
        return $this->returnData('usoscfdi',$usoscfdi,201);   
    }

    public function show($idUsoCFDI){
        try {
            $usoscfdi = UsoCFDI::findOrFail($idUsoCFDI);
            return $this->returnData('usoscfdi',$usoscfdi,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('UsoCFDI no encontrado',404,null); 
        }
    }
    
    public function destroy($idUsoCFDI){
        $UsoCFDI = UsoCFDI::find($idUsoCFDI);

        if (!$UsoCFDI) 
            return $this->returnEstatus('UsoCFDI no encontrado',404,null);             
        
            $UsoCFDI->delete();
        return $this->returnEstatus('UsoCFDI eliminado',200,null); 
    }

    public function update(Request $request, $idUsoCFDI){

        $UsoCFDI = UsoCFDI::find($idUsoCFDI);
        
        if (!$UsoCFDI) 
            return $this->returnEstatus('UsoCFDI no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                    'idUsoCFDI' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255',
                    'fisico' => 'required|max:255',
                    'moral' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $UsoCFDI->idUsoCFDI = $request->idUsoCFDI;
        $UsoCFDI->descripcion = strtoupper(trim($request->descripcion));
        $UsoCFDI->fisico = $request->fisico;
        $UsoCFDI->moral = $request->moral;
        $UsoCFDI->save();
        return $this->returnData('UsoCFDI',$UsoCFDI,200);
    }
        
    public function generaReporte(){
       return $this->imprimeCtl('usosCFDI',' USO CFDI ',['CLAVE', 'DESCRIPCIÓN','FISICA','MORAL'],[100, 250,50,50],'descripcion');
    }   

    public function exportaExcel() {
       return $this->exportaXLS('usosCFDI','idUsoCFDI',['CLAVE', 'DESCRIPCIÓN','FISICA','MORAL'],'descripcion');     
   }   
}
