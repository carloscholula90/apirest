<?php

namespace App\Http\Controllers\Api\seguridad;  
use App\Http\Controllers\Controller;
use App\Models\seguridad\PerfilAplicaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PerfilAplicacionController extends Controller
{
    public function index(){
       return DB::table('perfilAplicaciones as perfilApl')
                    ->select('perfilApl.idPerfil',
                             'perfilApl.idAplicacion',
                             'perfil.descripcion as perfil',
                             'aplicaciones.descripcion as aplicacion')
                    ->join('perfil', 'perfilApl.idPerfil', '=', 'perfil.idPerfil')
                    ->join('aplicaciones', 'perfilApl.idAplicacion', '=', 'aplicaciones.idAplicacion')
                    ->orderBy('perfilApl.idPerfil', 'asc')
                    ->orderBy('perfilApl.idAplicacion', 'asc')
                    ->get(); 
    }

    public function index2(){
        return DB::table('integra')
                     ->select(  
                            'perfil.idPerfil',
                            'aplicaciones.idAplicacion',
                            'perfil.descripcion as perfil',
                            'aplicaciones.descripcion as aplicacion',
                            DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre')
                                
                     )
                     ->join('persona', 'persona.uid', '=', 'integra.uid')
                     ->join('perfilAplicaciones', 'perfilAplicaciones.idPerfil', '=', 'integra.idPerfil')
                     ->join('perfil', 'integra.idPerfil', '=', 'perfil.idPerfil')
                     ->join('aplicaciones', 'aplicaciones.idAplicacion', '=', 'perfilAplicaciones.idAplicacion')                     
                     ->get(); 
     }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
                    'idAplicacion' => 'required|max:255',
                    'idPerfil' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
       
            $existe = DB::table('perfilAplicaciones as perfilApl')
                                    ->select( 'perfilApl.idPerfil')
                                    ->where('idPerfil', $request->idPerfil)
                                    ->where('idAplicacion', $request->idAplicacion)
                                    ->get(); 
            $cantidad = $existe->count();
            if($cantidad>0)
               return $this->returnEstatus('La aplicacion ya existe en el perfil ',400,null); 

            $perfiles = PerfilAplicaciones::create(['idPerfil' => $request->idPerfil,
                                                    'idAplicacion' => $request->idAplicacion]);  

            if (!$perfiles) 
                return $this->returnEstatus('Error al asignar la aplicacion al perfil',500,null);
            return $this->returnEstatus('El registro se guardo con exito',200,null); 
    }

    public function destroy($idPerfil,$idAplicacion){   
         $perfiles = PerfilAplicaciones::where('idPerfil', $idPerfil)
                  ->where('idAplicacion', $idAplicacion)
                  ->delete();
        return $this->returnEstatus('Aplicacion eliminada del perfil',200,null); 
    }

    public function update(Request $request){
        $perfiles = Perfil::find($request->idPerfil);

        if (!$perfiles) 
            return $this->returnEstatus('Perfil no encontrado',404,null); 

        $validator = Validator::make($request->all(), [
                                'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails())
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $perfiles->idPerfil = $request->idPerfil;
        $perfiles->descripcion = strtoupper(trim($request->descripcion));

        $perfiles->save();
        return $this->returnEstatus('Perfil actualizado',200,null); 

    }
    
}
