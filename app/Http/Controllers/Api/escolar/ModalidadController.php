<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Modalidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ModalidadController extends Controller
{
    public function index()
    {
        $modalidades = Modalidad::all();
        return $this->returnData('modalidades',$modalidades,200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Modalidad::max('idModalidad');
        $newId = $maxId ? $maxId+ 1 : 1;

        $modalidades = Modalidad::create([
            'idModalidad' => $newId,
            'descripcion' => strtoupper(trim($request->descripcion))
        ]);

        if (!$modalidades) 
            return $this->returnEstatus('Error al crear la modalidad',500,null); 
        $modalidades= Modalidad::findOrFail($newId);        
        return $this->returnData('modalidades',$modalidades,200);
    }

    public function show($id)
    {
        $modalidades = Modalidad::find($id);

        if (!$modalidades) 
            return $this->returnEstatus('Modalidad no encontrada',404,null); 
        return $this->returnData('modalidades',$modalidades,200);
    }

    public function destroy($id)
    {
        $modalidades = Modalidad::find($id);
        if (!$modalidades)
            return $this->returnEstatus('Modalidad no encontrada',404,null); 
        
        $modalidades->delete();
        return $this->returnEstatus('Modalidad eliminada',200,null); 
    }

    public function update(Request $request, $id)
    {
        $modalidades = Modalidad::find($id);

        if (!$modalidades)  
            return $this->returnEstatus('Modalidad no encontrada',404,null);

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $modalidades->idModalidad = $id;
        $modalidades->descripcion = strtoupper(trim($request->descripcion));

        $modalidades->save();

        return $this->returnEstatus('Modalidad actualizada',200,null); 

    }
}
