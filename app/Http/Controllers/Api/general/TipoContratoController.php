<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\TipoContrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TipoContratoController extends Controller
{
    public function index()
    {
        $tipoContrato = TipoContrato::all();
        return $this->returnData('Tipo Contrato',$tipoContrato,200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = TipoContrato::max('idTipoContrato');
        $newId = $maxId ? $maxId+ 1 : 1;

        try{
            $tipoContrato = TipoContrato::create([
                'idTipoContrato' => $newId,
                'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El tipo de contrato ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el tipo de contrato',400,null);
        }

        if (!$tipoContrato) 
            return $this->returnEstatus('Error al crear el tipo de contrato',500,null); 
        $tipoContrato= TipoContrato::findOrFail($newId);        
        return $this->returnData('Tipo contrato',$tipoContrato,200);
    }

    public function show($id)
    {
        $tipoContrato = TipoContrato::find($id);

        if (!$tipoContrato) 
            return $this->returnEstatus('Tipo contrato no encontrado',404,null); 
        return $this->returnData('Tipo contrato',$tipoContrato,200);
    }

    public function destroy($id)
    {
        $tipoContrato = TipoContrato::find($id);
        if (!$tipoContrato)
            return $this->returnEstatus('Tipo contrato no encontrado',404,null); 
        
        $tipoContrato->delete();
        return $this->returnEstatus('Tipo contrato eliminado',200,null); 
    }

    public function update(Request $request, $id)
    {
        $tipoContrato = TipoContrato::find($id);

        if (!$tipoContrato)  
            return $this->returnEstatus('Tipo contrato no encontrado',404,null);

        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $tipoContrato->idTipoContrato = $id;
        $tipoContrato->descripcion = strtoupper(trim($request->descripcion));

        $tipoContrato->save();

        return $this->returnEstatus('Tipo contrato actualizado',200,null); 

    }

    public function exportaExcel() {
        return $this->exportaXLS('TipoContrato','idTipoContrato', ['CLAVE','DESCRIPCIÓN'],'descripcion');     
    }

    public function generaReporte(){
       return $this->imprimeCtl('TipoContrato','tipo contrato',null,null,'descripcion');
   }
}
