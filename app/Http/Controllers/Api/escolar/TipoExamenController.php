<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\TipoExamen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TipoExamenController extends Controller{

    public function index(){       
        $tiposexamenes = TipoExamen::all();
        return $this->returnData('tiposexamenes',$tiposexamenes,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = TipoExamen::max('idTipoExamen');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $tiposexamenes = TipoExamen::create([
                            'idTipoExamen' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El TipoExamen ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el TipoExamen',400,null);
        }

        if (!$tiposexamenes) 
            return $this->returnEstatus('Error al crear el TipoExamen',500,null); 
        return $this->returnData('$tiposexamenes',$tiposexamenes,201);   
    }

    public function show($idTipoExamen){
        try {
            $tiposexamenes = TipoExamen::findOrFail($idTipoExamen);
            return $this->returnData('$tiposexamenes',$tiposexamenes,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('TipoExamen no encontrado',404,null); 
        }
    }
    
    public function destroy($idTipoExamen){
        $TipoExamen = TipoExamen::find($idTipoExamen);

        if (!$TipoExamen) 
            return $this->returnEstatus('TipoExamen no encontrado',404,null);             
        
            $TipoExamen->delete();
        return $this->returnEstatus('TipoExamen eliminado',200,null); 
    }

    public function update(Request $request, $idTipoExamen){

        $TipoExamen = TipoExamen::find($idTipoExamen);
        
        if (!$TipoExamen) 
            return $this->returnEstatus('TipoExamen no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                    'idTipoExamen' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $TipoExamen->idTipoExamen = $request->idTipoExamen;
        $TipoExamen->descripcion = strtoupper(trim($request->descripcion));
        $TipoExamen->save();
        return $this->returnData('TipoExamen',$TipoExamen,200);
    }

    public function updatePartial(Request $request, $idTipoExamen){

        $TipoExamen = TipoExamen::find($idTipoExamen);
        
        if (!$TipoExamen) 
            return $this->returnEstatus('TipoExamen no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idTipoExamen' => 'required|numeric|max:255',
                                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idTipoExamen')) 
            $TipoExamen->idTipoExamen = $request->idTipoExamen;        

        if ($request->has('descripcion')) 
            $TipoExamen->descripcion = strtoupper(trim($request->descripcion));        

        $TipoExamen->save();
        return $this->returnEstatus('TipoExamen actualizado',200,null);    
    }

     
    public function generaReporte()
    {
       return $this->imprimeCtl('tipoExamen',' tipo examen ',null,null,'descripcion');
    } 

    public function exportaExcel() {
       return $this->exportaXLS('tipoExamen','idTipoExamen',['CLAVE', 'DESCRIPCIÓN'],'descripcion');     
   }   
}
