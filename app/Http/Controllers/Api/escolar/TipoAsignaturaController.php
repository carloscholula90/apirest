<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\TipoAsignatura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TipoAsignaturaController extends Controller
{
    public function index()
    {
        $tipoasignatura = TipoAsignatura::all();
        return $this->returnData('Tipo Asignaturas',$tipoasignatura,200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = TipoAsignatura::max('idTipoAsignatura');
        $newId = $maxId ? $maxId+ 1 : 1;

        $tipoasignatura = TipoAsignatura::create([
            'idTipoAsignatura' => $newId,
            'descripcion' => strtoupper(trim($request->descripcion))
        ]);

        if (!$tipoasignatura) 
            return $this->returnEstatus('Error al crear el tipo de asignatura',500,null); 
        $tipoasignatura= TipoAsignatura::findOrFail($newId);        
        return $this->returnData('Tipo Asignaturas',$tipoasignatura,200);
    }

    public function show($id)
    {
        $tipoasignatura = TipoAsignatura::find($id);

        if (!$tipoasignatura) 
            return $this->returnEstatus('Tipo de asignatura no encontrada',404,null); 
        return $this->returnData('Tipo Asignaturas',$tipoasignatura,200);
    }

    public function destroy($id)
    {
        $tipoasignatura = TipoAsignatura::find($id);
        if (!$tipoasignatura)
            return $this->returnEstatus('Tipo de asignatura no encontrada',404,null); 
        
        $tipoasignatura->delete();
        return $this->returnEstatus('Tipo asignatura eliminada',200,null); 
    }

    public function update(Request $request, $id)
    {
        $tipoasignatura = TipoAsignatura::find($id);

        if (!$tipoasignatura)  
            return $this->returnEstatus('Tipo asignatura no encontrada',404,null);

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $tipoasignatura->idTipoAsignatura = $id;
        $tipoasignatura->descripcion = strtoupper(trim($request->descripcion));

        $tipoasignatura->save();

        return $this->returnEstatus('Tipo asignatura actualizada',200,null); 

    }
}
