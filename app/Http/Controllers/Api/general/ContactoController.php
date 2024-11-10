<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\Contacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactoController extends Controller{

    public function index(){       
        $contactos = Contacto::all();
        return $this->returnData('contactos',$contactos,200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
                                        'uid' => 'required|numeric',
                                        'idParentesco' => 'required|numeric',
                                        'idTipoContacto' => 'required|numeric',
                                        'dato' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
        
            $contactos = Contacto::where('idParentesco', $request->idParentesco)
                                    ->where('idTipoContacto',$request->idTipoContacto)
                                    ->where('uid', $request->uid)
                                    ->where('dato',trim($request->dato));   
            
            if ($contactos) 
                return $this->returnEstatus('El dato con el tipo de contacto ya existe ',404,null);  
                         

            $maxId = Contacto::where('idParentesco', $request->idParentesco)
                                    ->where('idTipoContacto', $request->idTipoContacto)
                                    ->where('uid', $request->uid)
                                    ->max('consecutivo');
  
            $newId = $maxId ? $maxId + 1 : 1; 
            try {
                $contactos = Contacto::create([
                                'consecutivo' => $newId,
                                'idParentesco' => $request->idParentesco, 
                                'idTipoContacto' => $request->idTipoContacto,
                                'uid' => $request->uid,
                                'dato' => trim($request->dato)
                ]);  
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El Contacto ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el Contacto',400,null);
        }

        if (!$contactos) 
            return $this->returnEstatus('Error al crear el Contacto',500,null); 
        return $this->returnData('contactos',$contactos,201);   
    }

    public function show($uid,$idParentesco,$idTipoContacto){
        try {
            $contactos = Contacto::where('idParentesco', $idParentesco)
                                ->where('idTipoContacto',$idTipoContacto)
                                ->where('uid', $uid);

            return $this->returnData('$contactos',$contactos,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Contacto no encontrado',404,null); 
        }
    }
    
    public function destroy($uid,$idParentesco,$idTipoContacto,$consecutivo){
        $contactos = Contacto::where('idParentesco', $idParentesco)
                              ->where('idTipoContacto',$idTipoContacto)
                              ->where('uid', $uid)
                              ->where('consecutivo',$consecutivo); 
        $contactos->delete();

        if (!$contactos) 
            return $this->returnEstatus('Contacto no encontrado',404,null);  
        return $this->returnEstatus('Contacto eliminado',200,null); 
    }
}
