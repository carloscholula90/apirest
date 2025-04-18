<?php

namespace App\Http\Controllers\Api\seguridad;  
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PerfilesPersonaController extends Controller
{
    public function index()
    {
        $perfilesPersona= DB::table('integra')
                        ->select('integra.uid',
                                  DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre'),
                                 'perfil.descripcion as perfil')
                        ->join('perfil', 'integra.idPerfil', '=', 'perfil.idPerfil')
                        ->join('persona', 'persona.uid', '=', 'integra.uid')                        
                        ->get(); 
        return $this->returnData('perfilesPersona',$perfilesPersona,200);
    }

    public function destroy($uid)
    {
        $actualiza = DB::table('integra')
                        ->where('uid', $uid)
                         ->update(['idPerfil' => null]);

        return $this->returnEstatus('Perfil eliminado',200,null); 
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
                            'idPerfil' => 'required|max:255'

        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $actualiza = DB::table('integra')
                        ->where('uid', $request->uid)
                        ->update(['idPerfil' => $request->idPerfil]);
        
        if ($actualiza === 0) 
            return $this->returnEstatus('No se encontró el perfil o no hubo cambios', 404, null);
                        
        return $this->returnEstatus('Perfil actualizado',200,null); 
    }

    public function update($uid,$idPerfil)
    {
        $actualiza = DB::table('integra')
                        ->where('uid', $uid)  
                        ->update(['idPerfil' => $idPerfil]);
        
        if ($actualiza === 0) 
            return $this->returnEstatus('No se encontró el perfil o no hubo cambios', 404, null);
                        
        return $this->returnEstatus('Perfil actualizado',200,null); 
    }
}
