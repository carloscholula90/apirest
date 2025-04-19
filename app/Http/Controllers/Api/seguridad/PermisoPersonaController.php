<?php
namespace App\Http\Controllers\Api\seguridad; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\seguridad\PermisoPersona;
use Illuminate\Support\Facades\DB;   

class PermisoPersonaController extends Controller
{  
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       $permisos = DB::table('integra')
                    ->select('integra.uid',
                                DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre'),
                                'aplicaciones.descripcion as aplicacion','aplicaciones.secuencia')
                    ->join('permisosPersona', 'permisosPersona.uid', '=', 'integra.uid')
                    ->join('persona', 'persona.uid', '=', 'integra.uid')   
                    ->join('aplicaciones', 'aplicaciones.idAplicacion', '=', 'permisosPersona.idAplicacion')                      
                    ->get();
       
       DB::table('permisosPersona')
                        ->get();
        return $this->returnData('Permisos',$permisos,200);  
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required|max:255',
            'idAplicacion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
        return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxSeq = PermisoPersona::where('uid', $request->uid)->max('secuencia');        
        $nextSeq = ($maxSeq === null) ? 1 : $maxSeq + 1;

        $create= PermisoPersona::create([
                                    'uid' => $request->uid,
                                    'secuencia' => $nextSeq,
                                    'idAplicacion' => $request->idAplicacion 
                                ]);

        if ($create === 0) 
            return $this->returnEstatus('Error en la insercion', 404, null);
                
        return $this->returnEstatus('Registro creado',200,null); 
    }

    /**
     * Display the specified resource.
     */
    public function show($id,$idRol)
    {
          $permisos = DB::table('aplicacionesUsuario')
                        ->where('uid', $id)
                        ->where('idRol', $idRol)
                        ->get();

          if (!$permisos)
            return $this->returnEstatus('Sin aplicaciones en el rol. Favor de validar',400,null); 
          return $this->returnData('Permisos',$permisos,200);  
    }

       /**
     * Remove the specified resource from storage.
     */
    public function destroy($uid,$secuencia)
    {
        $destroy = DB::table('permisosPersona')
                            ->where('uid', $uid  )
                            ->where('secuencia', $secuencia)
                            ->delete();

        if ($destroy === 0) 
            return $this->returnEstatus('Error en la eliminacion', 404, null);
                
        return $this->returnEstatus('Registro eliminado',200,null); 
    }
}
