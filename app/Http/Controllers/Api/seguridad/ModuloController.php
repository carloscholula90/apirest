<?php

namespace App\Http\Controllers\Api\seguridad;  
use App\Http\Controllers\Controller;
use App\Models\seguridad\Modulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ModuloController extends Controller
{
    public function index()
    {
        $modulos = Modulo::all();
        return $this->returnData('modulos',$modulos,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Modulo::max('idModulo');
        $newId = $maxId ? $maxId+ 1 : 1;

        $modulos = Modulo::create([
                        'idModulo' => $newId,
                        'descripcion' => $request->descripcion
        ]);

        if (!$modulos) return 
            $this->returnEstatus('Error al crear el módulo',500,null); 
        
        $modulos = Modulo::findOrFail($newId);
        return $this->returnData('modulos',$modulos,200);
    }

    public function show($id)
    {
        $modulos = Modulo::find($id);

        if (!$modulos) 
            return $this->returnEstatus('Modulo no encontrado',404,null); 

        return $this->returnData('modulos',$modulos,200);
    }

    public function destroy($id)
    {
        $modulos = Modulo::find($id);

        if (!$modulos) 
            return $this->returnEstatus('Modulo no encontrado',404,null);         
        
        $modulos->delete();

        return $this->returnEstatus('Módulo eliminado',200,null); 
    }

    public function update(Request $request, $id)
    {
        $modulos = Modulo::find($id);

        if (!$modulos) 
            return $this->returnEstatus('Modulo no encontrado',404,null); 

        $validator = Validator::make($request->all(), [
                                'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails())
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $modulos->idModulo = $id;
        $modulos->descripcion = strtoupper(trim($request->descripcion));

        $modulos->save();
        return $this->returnEstatus('Módulo actualizado',200,null); 

    }
}
