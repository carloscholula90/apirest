<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Idiomas;
use Illuminate\Http\Request;  
use Illuminate\Support\Facades\Validator;

class IdiomasController extends Controller
{
    public function index() {             
        $idiomas = Idiomas::all();
        return $this->returnData('idiomas',$idiomas,200);
    }

    public function show($idIdioma) {
        try {
            // Busca el idioma por ID y lanza una excepción si no se encuentra
            $idiomas = Idiomas::findOrFail($idIdiomas);
            return $this->returnData('idiomas',$idIdioma,200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si el idioma no se encuentra, retorna un mensaje de error con estado 404
            return $this->returnEstatus('Idioma no encontrado',400,null); 
        }
    }

    public function store(Request $request) {
        
        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $maxIdIdiomas = Idiomas::max('idIdioma');
        $newIdIdiomas = $maxIdIdiomas ? $maxIdIdiomas + 1 : 1;
        $idiomas = Idiomas::create([
                            'idIdioma' => $newIdIdiomas,
                            'descripcion' => strtoupper(trim($request->descripcion))
        ]);

        if (!$idiomas)
            return $this->returnEstatus('Error al crear el idioma',500,null); 

        $idiomas = Idiomas::findOrFail($newIdIdiomas);
        return $this->returnData('idiomas',$idiomas,200);
    }

    public function updatePartial(Request $request, $idIdioma) {

        $idiomas = Idiomas::find($idIdioma);
        if (!$idiomas) {
            return response()->json(['message' => 'Idioma no encontrado', 'status' => 404], 404);
        }

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors());

        $idiomas->idIdioma = $request->idIdioma;
        $idiomas->descripcion = strtoupper(trim($request->descripcion));
        $idiomas->save();

        return $this->returnEstatus('El registro fue actualizado con éxito',200,null);
    
    }

    public function destroy($idIdioma) {
        $ididioma = Idiomas::find($idIdioma);
        if (!$ididioma)
            return $this->returnEstatus('Idioma no encontrado',400,null); 
        
        $ididioma->delete();
        return $this->returnEstatus('Idioma eliminado',200,null);        
    }

    


 
}