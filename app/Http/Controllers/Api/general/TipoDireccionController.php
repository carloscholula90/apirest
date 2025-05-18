<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\TipoDireccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TipoDireccionController extends Controller{

    public function index(){       
        $tipodirecciones = TipoDireccion::all();
        return $this->returnData('tipodirecciones',$tipodirecciones,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = TipoDireccion::max('idTipoDireccion');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $tipodirecciones = TipoDireccion::create([
                            'idTipoDireccion' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El TipoDireccion ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el TipoDireccion',400,null);
        }

        if (!$tipodirecciones) 
            return $this->returnEstatus('Error al crear el TipoDireccion',500,null); 
        return $this->returnData('$tipodirecciones',$tipodirecciones,200);   
    }

    public function show($idTipoDireccion){
        try {
            $$tipodirecciones = TipoDireccion::findOrFail($idTipoDireccion);
            return $this->returnData('$tipodirecciones',$tipodirecciones,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('TipoDireccion no encontrado',404,null); 
        }
    }
    
    public function destroy($idTipoDireccion){
        $TipoDireccion = TipoDireccion::find($idTipoDireccion);

        if (!$TipoDireccion) 
            return $this->returnEstatus('TipoDireccion no encontrado',404,null);             
        
            $TipoDireccion->delete();
        return $this->returnEstatus('TipoDireccion eliminado',200,null); 
    }

    public function update(Request $request, $idTipoDireccion){

        $TipoDireccion = TipoDireccion::find($idTipoDireccion);
        
        if (!$TipoDireccion) 
            return $this->returnEstatus('TipoDireccion no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                    'idTipoDireccion' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $TipoDireccion->idTipoDireccion = $request->idTipoDireccion;
        $TipoDireccion->descripcion = strtoupper(trim($request->descripcion));
        $TipoDireccion->save();
        return $this->returnData('TipoDireccion',$TipoDireccion,200);
    }

    public function updatePartial(Request $request, $idTipoDireccion){

        $TipoDireccion = TipoDireccion::find($idTipoDireccion);
        
        if (!$TipoDireccion) 
            return $this->returnEstatus('TipoDireccion no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idTipoDireccion' => 'required|numeric|max:255',
                                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idTipoDireccion')) 
            $TipoDireccion->idTipoDireccion = $request->idTipoDireccion;        

        if ($request->has('descripcion')) 
            $TipoDireccion->descripcion = strtoupper(trim($request->descripcion));        

        $TipoDireccion->save();
        return $this->returnEstatus('TipoDireccion actualizado',200,null);    
    }

    public function exportaExcel() {
        return $this->exportaXLS('tipoDireccion','idTipoDireccion', ['CLAVE','DESCRIPCIÓN'],'descripcion');     
    }

    public function generaReporte(){
        return $this->imprimeCtl('tipoDireccion','tipo de direccion',null,null,'descripcion');
    }

}
