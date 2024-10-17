<?php

namespace App\Http\Controllers\Api\seguridad;  
use App\Http\Controllers\Controller;
use App\Models\seguridad\PermisoRol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermisoRolController extends Controller
{
    public function index() {

        $permisosrol = PermisoRol::join('aplicaciones','permisosRol.idAplicacion','=','aplicaciones.idAplicacion')
                                ->join('rol','permisosRol.idRol','=','rol.idRol')
                                ->select('aplicaciones.idAplicacion',
                                         'aplicaciones.descripcion as appDescripcion',
                                         'rol.idRol',
                                         'rol.descripcion as rolDescripcion'
                                        )
                                ->get();

        return $this->returnData('permisoRol',$permisosrol,200);
    }

    public function show($idApp,$idRol) {

        $permisosrol = PermisoRol::join('aplicaciones','permisosRol.idAplicacion','=','aplicaciones.idAplicacion')
                                ->join('rol','permisosRol.idRol','=','rol.idRol')
                                ->select('aplicaciones.idAplicacion',
                                         'aplicaciones.descripcion as appDescripcion',
                                         'rol.idRol',
                                         'rol.descripcion as rolDescripcion'
                                        )
                                ->where('permisosRol.idAplicacion', '=', $idApp)
                                ->where('permisosRol.idRol', '=', $idRol)                                        
                                ->get();

        return $this->returnData('permisosRol',$permisosrol,200);
    }
    
    public function destroy($idApp,$idRol) {

        $permisosrol = PermisoRol::where('idPais',$idApp)
                                  ->where('idEstado',$idRol);
        
        if (!$permisosrol)
            return $this->returnEstatus('permiso-rol no encontrado',404,null);
        
        $permisosrol->delete();
            return $this->returnEstatus('permiso rol eliminado',404,null);
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
