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
        try{
            $perfiles = Perfil::create([
                            'idPerfil' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El perfil ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el perfil',400,null);
        }

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
        
         $actualiza = DB::table('integra')
                        ->where('idPerfil', $idPerfil)
                         ->update(['idPerfil' => null]);

       
        $perfiles->delete();

        return $this->returnEstatus('Perfil eliminado',200,null); 
    }

    public function update(Request $request)
    {
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
    
    public function exportaExcel() {
        return $this->exportaXLS('perfil','idPerfil', ['CLAVE','DESCRIPCIÓN'],'descripcion');     
    }

    public function generaReporte()
    {
        return $this->imprimeCtl('perfil','perfil',null,null,'descripcion');
    }
}
