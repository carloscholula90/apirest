<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Medio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedioController extends Controller
{
    public function index(){
        $medios = Medio::all();
        return $this->returnData('medios',$medios,200);
    }

    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors());        

        $maxIdMedio = Medio::max('idMedio');
        $newIdMedio = $maxIdMedio ? $maxIdMedio + 1 : 1;
        $medios = Medio::create([
                            'idMedio' => $newIdMedio,
                            'descripcion' => strtoupper(trim($request->descripcion))
        ]);

        if (!$medios) 
            return $this->returnEstatus('Error al crear el medio',500,null);
        
        $medios = Medio::findOrFail($newIdMedio);
        return $this->returnData('medios',$medios,200);

    }

    public function show($idMedio){
        try {
            // Busca el medio por ID y lanza una excepción si no se encuentra
            $medios = Medio::findOrFail($idMedio);
            return $this->returnData('medios',$medios,200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el medio no se encuentra, retorna un mensaje de error con estado 404
            return $this->returnEstatus('Medio no encontrado',400,null);            
        }
    }
    
    public function destroy($idMedio){
        $medios = Medio::find($idMedio);
        if (!$medios) 
            return $this->returnEstatus('Medio no encontrado',400,null); 
        $medios->delete();
            return $this->returnEstatus('Medio eliminado',200,null);  
    }

    public function update(Request $request, $idMedio){

        $medios = Medio::find($idMedio);
        if (!$medios) 
            return $this->returnEstatus('Medio no encontrado',400,null); 

        $validator = Validator::make($request->all(), [
                    'idMedio' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
                 return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors());

        $medios->idMedio = $request->idMedio;
        $medios->descripcion = strtoupper(trim($request->descripcion));
        $medios->save();

        return $this->returnEstatus('Medio actualizao',200,null);
    }
}  
