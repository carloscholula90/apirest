<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Asignatura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AsignaturaController extends Controller{

    public function index(){       
        $asignaturas = Asignatura::all();
        return $this->returnData('asignaturas',$asignaturas,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Asignatura::max('idAsignatura');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $asignaturas = Asignatura::create([
                            'idAsignatura' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('La asignatura ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar la asignatura',400,null);
        }

        if (!$asignaturas) 
            return $this->returnEstatus('Error al crear la asignatura',500,null); 
        return $this->returnData('asignaturas',$asignaturas,200);   
    }

    public function show($idAsignatura){
        try {
            $asignaturas = Asignatura::findOrFail($idAsignatura);
            return $this->returnData('asignaturas',$asignaturas,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Asignatura no encontrada',404,null); 
        }
    }
    
    public function destroy($idAsignatura){
        $asignatura = Asignatura::find($idAsignatura);

        if (!$asignatura) 
            return $this->returnEstatus('Asignatura no encontrada',404,null);             
        try {
            $asignatura->delete();
            return $this->returnEstatus('Asignatura eliminada',200,null); 
        } catch (QueryException $e) {
        if ($e->getCode() == '23000') {
            // Este es el código de error para integridad referencial
            return $this->returnEstatus('No se puede eliminar la asignatura, esta siendo utilizada ya en un plan de estudios',400,null); 
        } 
        }    
    }

    public function update(Request $request, $idAsignatura){

        $asignatura = Asignatura::find($idAsignatura);
        
        if (!$asignatura) 
            return $this->returnEstatus('Asignatura no encontrada',404,null);             

        $validator = Validator::make($request->all(), [
                    'idAsignatura' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $asignatura->idAsignatura = $request->idAsignatura;
        $asignatura->descripcion = strtoupper(trim($request->descripcion));
        $asignatura->save();
        return $this->returnData('Asignatura',$asignatura,200);
    }

    public function updatePartial(Request $request, $idAsignatura){

        $asignatura = Asignatura::find($idAsignatura);
        
        if (!$asignatura) 
            return $this->returnEstatus('Asignatura no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idAsignatura' => 'required|numeric|max:255',
                                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idAsignatura')) 
            $asignatura->idAsignatura = $request->idAsignatura;        

        if ($request->has('descripcion')) 
            $asignatura->descripcion = strtoupper(trim($request->descripcion));        

        $asignatura->save();
        return $this->returnEstatus('Asignatura actualizado',200,null);    
    }

     
    public function generaReporte()
    {
       return $this->imprimeCtl('asignatura',' asignaturas ',null,null,'descripcion');
    } 

    public function exportaExcel() {
       return $this->exportaXLS('asignatura','idAsignatura',['CLAVE', 'DESCRIPCIÓN'],'descripcion');     
   }   
}
