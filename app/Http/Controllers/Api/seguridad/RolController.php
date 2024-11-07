<?php

namespace App\Http\Controllers\Api\seguridad;  
use App\Http\Controllers\Controller;
use App\Models\seguridad\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::all();
        
        return $this->returnData('roles',$roles,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Rol::max('idRol');
        $newId = $maxId ? $maxId+ 1 : 1;
        try{
            $roles = Rol::create([
                            'idRol' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El rol ya se encuentra dado de alta',400,null);                
            return $this->returnEstatus('Error al insertar el rol',400,null);
        }
        if (!$roles) return 
            $this->returnEstatus('Error al crear el rol',500,null); 
        
        $roles = Rol::findOrFail($newId);
        return $this->returnData('roles',$roles,200);
    }


    public function show($id)
    {
        $roles = Rol::find($id);

        if (!$roles) 
            return $this->returnEstatus('Rol no encontrado',404,null); 

        return $this->returnData('roles',$roles,200);
    }

    public function destroy($id)
    {
        $roles = Rol::find($id);

        if (!$roles) 
            return $this->returnEstatus('Rol no encontrado',404,null);         
        
        $roles->delete();

        return $this->returnEstatus('Rol eliminado',200,null); 
    }

    public function update(Request $request)
    {
        $roles = Rol::find($request->idRol);

        if (!$roles) 
            return $this->returnEstatus('Rol no encontrado',404,null); 

        $validator = Validator::make($request->all(), [
                                'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails())
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $roles->idRol = $request->idRol;
        $roles->descripcion = strtoupper(trim($request->descripcion));

        $roles->save();
        return $this->returnEstatus('Rol actualizado',200,null); 

    }
}
