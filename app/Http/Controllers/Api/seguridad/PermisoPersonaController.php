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
                                'aplicaciones.descripcion as aplicacion')
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
    public function create()
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required|max:255',
            'idAplicacion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
        return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxSecuencial = $persona->contactos
        ->where('idTipoContacto', $tipo)
        ->max('consecutivo');

        
        $actualiza = DB::table('integra')
                ->where('uid', $request->uid)
                ->update(['idPerfil' => $request->idPerfil]);

        if ($actualiza === 0) 
        return $this->returnEstatus('No se encontró el perfil o no hubo cambios', 404, null);
                
        return $this->returnEstatus('Perfil actualizado',200,null); 
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
