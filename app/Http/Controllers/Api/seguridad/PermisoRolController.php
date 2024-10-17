<?php

namespace App\Http\Controllers\Api\seguridad;  
use App\Http\Controllers\Controller;
use App\Models\seguridad\PermisoRol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermisoRolController extends Controller
{
    public function index()
    {
        $permisorol = PermisoRol::all();
        
        /*return $this->returnData('perfiles',$perfiles,200);*/
    }

    /*public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = PerfilRol::max('idPerfil');
        $newId = $maxId ? $maxId+ 1 : 1;

        $perfiles = PerfilRol::create([
                        'idPerfil' => $newId,
                        'descripcion' => strtoupper(trim($request->descripcion))
        ]);

        if (!$perfiles) return 
            $this->returnEstatus('Error al crear el perfil',500,null); 
        
        $perfiles = PerfilRol::findOrFail($newId);
        return $this->returnData('perfiles',$perfiles,200);
    }


    public function show($id)
    {
        $perfiles = PerfilRol::find($id);

        if (!$perfiles) 
            return $this->returnEstatus('Perfil no encontrado',404,null); 

        return $this->returnData('perfiles',$perfiles,200);
    }

    public function destroy($id)
    {
        $perfiles = PerfilRol::find($id);

        if (!$perfiles) 
            return $this->returnEstatus('Perfil no encontrado',404,null);         
        
        $perfiles->delete();

        return $this->returnEstatus('Perfil eliminado',200,null); 
    }

    public function update(Request $request, $id)
    {
        $perfiles = PerfilRol::find($id);

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

    }*/
}
