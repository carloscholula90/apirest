<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\TipoContacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TipoContactoController extends Controller
{
    public function index()
    {
        $tipocontacto = TipoContacto::all();
        return $this->returnData('Tipo Contacto',$tipocontacto,200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = TipoContacto::max('idTipoContacto');
        $newId = $maxId ? $maxId+ 1 : 1;

        try{
            $tipocontacto = TipoContacto::create([
                'idTipoContacto' => $newId,
                'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El tipo de contacto ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el tipo de contacto',400,null);
        }

        if (!$tipocontacto) 
            return $this->returnEstatus('Error al crear el tipo de contacto',500,null); 
        $tipocontacto= TipoContacto::findOrFail($newId);        
        return $this->returnData('Tipo Contacto',$tipocontacto,200);
    }

    public function show($id)
    {
        $tipocontacto = TipoContacto::find($id);

        if (!$tipocontacto) 
            return $this->returnEstatus('Tipo Contacto no encontrado',404,null); 
        return $this->returnData('Tipo Contacto',$tipocontacto,200);
    }

    public function destroy($id)
    {
        $tipocontacto = TipoContacto::find($id);
        if (!$tipocontacto)
            return $this->returnEstatus('Tipo Contacto no encontrado',404,null); 
        
        $tipocontacto->delete();
        return $this->returnEstatus('Tipo Contacto eliminado',200,null); 
    }

    public function update(Request $request, $id)
    {
        $tipocontacto = TipoContacto::find($id);

        if (!$tipocontacto)  
            return $this->returnEstatus('Tipo Contacto no encontrado',404,null);

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $tipocontacto->idTipoContacto = $id;
        $tipocontacto->descripcion = strtoupper(trim($request->descripcion));

        $tipocontacto->save();

        return $this->returnEstatus('Tipo Contacto actualizado',200,null); 

    }

    public function exportaExcel() {
        return $this->exportaXLS('tipocontacto','idTipoContacto', ['CLAVE','DESCRIPCIÓN']);     
    }

    public function generaReporte(){
       return $this->imprimeCtl('tipocontacto','tipo contacto');
   }
}
