<?php

namespace App\Http\Controllers\Api\seguridad;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\seguridad\RolSeg;
use Illuminate\Support\Facades\Validator;

class RolSegController extends Controller
{
    public function index()
    {
        $rolesseg = RolSeg::all();
        return $this->returnData('rolesseg',$rolesseg,200);        
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors());
        
        $maxIdRol = RolSeg::max('idRol');
        $newIdRol = $maxIdRol ? $maxIdRol + 1 : 1;
        $rolSeg = RolSeg::create([
                                'idRol' => $newIdRol,
                                'nombre' => strtoupper(trim($request->nombre))
        ]);

        if (!$rolSeg) 
            return $this->returnEstatus('Error al crear el rol de seguridad',500,null);

        $rolSeg = RolSeg::findOrFail($newIdRol);        
        return $this->returnData('rolSeg',$rolSeg,200);
    }

    public function show($id)
    {
        $rolSeg = RolSeg::find($id);
        if (!$RolSeg)
            return $this->returnEstatus('Rol de seguridad no encontrado',404,null);
        return $this->returnData('rolSeg',$rolSeg,200);
    }

    public function destroy($id)
    {
        $rolSeg = RolSeg::find($id);

        if (!$rolSeg) 
            return $this->returnEstatus('Rol de seguridad no encontrado',404,null);
        
        $rolSeg->delete();
        return $this->returnEstatus('Rol de seguridad eliminado',200,null);        
    }

    
    public function updatePartial(Request $request, $idRol)
    {
        $rolSeg = RolSeg::find($idRol);

        if (!$rolSeg) 
            return $this->returnEstatus('Rol de seguridad no encontrado',404,null);

        $rolSeg->idRol = $idRol;        

        if ($request->has('nombre')) 
            $rolSeg->nombre = strtoupper(trim($request->nombre));        

        $rolSeg->save();
        return $this->returnEstatus('Rol de seguridad actualizado',200,null); 
    }
}
