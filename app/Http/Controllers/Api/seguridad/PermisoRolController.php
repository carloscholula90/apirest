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
     


    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                        'idAplicacion' => 'required|max:255',
                        'idRol' => 'required|max:255',
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $permisosrol = PermisoRol::join('aplicaciones','permisosRol.idAplicacion','=','aplicaciones.idAplicacion')
                        ->join('rol','permisosRol.idRol','=','rol.idRol')
                        ->select('aplicaciones.idAplicacion',
                                'aplicaciones.descripcion as appDescripcion',
                                'rol.idRol',
                                'rol.descripcion as rolDescripcion'
                                )
                        ->where('permisosRol.idAplicacion', '=', $request->idAplicacion)
                        ->where('permisosRol.idRol', '=', $request->idRol)                                        
                        ->get();

        
        if (!$permisosrol) return 
           $this->returnEstatus('La aplicación ya se encuentra asignada al rol',500,null); 
                   

        $permisosrol = PermisoRol::create([
                        'idAplicacion' =>  $request->idAplicacion,
                        'idRol' =>  $request->idRol]);   

        if (!$permisosrol) return 
            $this->returnEstatus('Error al crear el perfil',500,null); 
        
        return $this->returnEstatus('Se agregó con éxito',500,null); 
    }

  /*  public function update(Request $request, $id)
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
