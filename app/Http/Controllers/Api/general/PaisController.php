<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Pais;
use Illuminate\Http\Request;  
use Illuminate\Support\Facades\Validator;

class PaisController extends Controller
{
    public function index(){             
        $pais = Pais::all();
        return $this->returnData('pais',$pais,200);
    }

    public function store(Request $request){
        
        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255',
                    'nacionalidad' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $maxIdPais = Pais::max('idPais');
        $newIdPais = $maxIdPais ? $maxIdPais + 1 : 1;
        $pais = Pais::create([
                            'idPais' => $newIdPais,
                            'descripcion' => $request->descripcion,
                            'nacionalidad' => $request->nacionalidad
        ]);

        if (!$pais)
            return $this->returnEstatus('Error al crear el pais',500,null); 

        $pais = Pais::findOrFail($newIdPais);
        return $this->returnData('pais',$pais,200);
    }

    public function show($idPais){
        try {
            // Busca el pais por ID y lanza una excepción si no se encuentra
            $pais = Pais::findOrFail($idPais);
            return $this->returnData('pais',$pais,200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el pais no se encuentra, retorna un mensaje de error con estado 404
            return $this->returnEstatus('Pais no encontrado',400,null); 
        }
    }
    
    public function destroy($idPais){
        $pais = Pais::find($idPais);
        if (!$pais)
            return $this->returnEstatus('Pais no encontrado',400,null); 
        
        $pais->delete();
        return $this->returnEstatus('Pais eliminado',200,null);        
    }


    public function updatePartial(Request $request, $idPais){

        if ($idPais==null) 
        return $this->returnEstatus('Agregue el ID del pais',400,null); 

        $pais = Pais::find($idPais);     
      
        if ($request->has('idPais')) 
            $pais->idPais = $request->idPais;        

        if ($request->has('descripcion')) 
            $pais->descripcion = strtoupper(trim($request->descripcion));        

        if ($request->has('nacionalidad')) 
            $pais->nacionalidad = strtoupper(trim($request->nacionalidad));
          
        $pais->save();
        return $this->returnEstatus('Pais actualizado',200,null); 
    }

    public function nacionalidad(){             
        $nacionalidades = Pais::select('nacionalidad')->get();
        $nacionalidadesArray = $nacionalidades->map(function ($pais) {
            return $pais->nacionalidad;
        })->toArray();    
        return $this->returnData('nacionalidad',$nacionalidadesArray,200);
    }
    
    public function mostrarNacionalidad($idPais){
        try {
            $pais = Pais::findOrFail($idPais);
            return $this->returnData('pais',$pais,200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Pais no encontrado',404,null); 
        }
    }

    public function buscaNacionalidad($idPais){
        try {
            $pais = Pais::findOrFail($idPais);
            return $this->returnData('nacionalidad',$pais->nacionalidad,200);            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Pais no encontrado',404,null); 
        }
    }
    
    public function exportaExcel() {
        return $this->exportaXLS('pais','idPais',['CLAVE', 'DESCRIPCIÓN','NACIONALIDAD']);     
    }  

    public function generaReporte()
     {
        return $this->imprimeCtl('pais',' pais ',['CLAVE', 'DESCRIPCIÓN','NACIONALIDAD'],[100, 200,200],'descripcion');
     }  
}
