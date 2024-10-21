<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Escolaridad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EscolaridadController extends Controller
{
    public function index()
    {
        $escolaridad = Escolaridad::all();
        return $this->returnData('Escolaridades',$escolaridad,200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Escolaridad::max('idEscolaridad');
        $newId = $maxId ? $maxId+ 1 : 1;

        $escolaridad = Escolaridad::create([
            'idEscolaridad' => $newId,
            'descripcion' => strtoupper(trim($request->descripcion))
        ]);

        if (!$escolaridad) 
            return $this->returnEstatus('Error al crear la escolaridad',500,null); 
        $escolaridad= Escolaridad::findOrFail($newId);        
        return $this->returnData('Escolaridades',$escolaridad,200);
    }

    public function show($id)
    {
        $escolaridad = Escolaridad::find($id);

        if (!$escolaridad) 
            return $this->returnEstatus('Escolaridad no encontrada',404,null); 
        return $this->returnData('Escolaridades',$escolaridad,200);
    }

    public function destroy($id)
    {
        $escolaridad = Escolaridad::find($id);
        if (!$escolaridad)
            return $this->returnEstatus('Escolaridad no encontrada',404,null); 
        
        $escolaridad->delete();
        return $this->returnEstatus('Escolaridad eliminada',200,null); 
    }

    public function update(Request $request, $id)
    {
        $escolaridad = Escolaridad::find($id);

        if (!$escolaridad)  
            return $this->returnEstatus('Escolaridad no encontrada',404,null);

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $escolaridad->idEscolaridad = $id;
        $escolaridad->descripcion = strtoupper(trim($request->descripcion));

        $escolaridad->save();

        return $this->returnEstatus('Escolaridad actualizada',200,null); 

    }
}