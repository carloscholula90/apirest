<?php

namespace App\Http\Controllers\Api\seguridad;  
use App\Http\Controllers\Controller;
use App\Models\seguridad\Perfil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PerfilController extends Controller
{
    public function index()
    {
        $perfiles = Perfil::all();
        
        return $this->returnData('perfiles',$perfiles,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Perfil::max('idPerfil');
        $newId = $maxId ? $maxId+ 1 : 1;

        $perfiles = Perfil::create([
                        'idPerfil' => $newId,
                        'descripcion' => strtoupper(trim($request->descripcion))
        ]);

        if (!$perfiles) return 
            $this->returnEstatus('Error al crear el perfil',500,null); 
        
        $perfiles = Perfil::findOrFail($newId);
        return $this->returnData('perfiles',$perfiles,200);
    }


    public function show($id)
    {
        $perfiles = Perfil::find($id);

        if (!$perfiles) 
            return $this->returnEstatus('Perfil no encontrado',404,null); 

        return $this->returnData('perfiles',$perfiles,200);
    }

    public function destroy($id)
    {
        $perfiles = Perfil::find($id);

        if (!$perfiles) 
            return $this->returnEstatus('Perfil no encontrado',404,null);         
        
        $perfiles->delete();

        return $this->returnEstatus('Perfil eliminado',200,null); 
    }

    public function update(Request $request, $id)
    {
        $perfiles = Perfil::find($id);

        if (!$perfiles) 
            return $this->returnEstatus('Perfil no encontrado',404,null); 

        $validator = Validator::make($request->all(), [
                                'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails())
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $modulos->idModulo = $id;
        $modulos->descripcion = strtoupper(trim($request->descripcion));

        $modulos->save();
        return $this->returnEstatus('Perfil actualizado',200,null); 

    }
}
